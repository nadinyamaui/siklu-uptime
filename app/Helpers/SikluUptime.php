<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SikluUptime
{
    const DOWN_MODULATIONS = ['down', 'bpsk1', 'bpsk2'];
    protected Carbon $start;
    protected Carbon $end;
    protected int $seconds;

    protected string $period;

    protected string $content;

    public array $modulations = [
        'DOWN' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'BPSK1' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'BPSK2' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QPSK1' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QPSK2' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QPSK3' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        '8PSK' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QAM16' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QAM32' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QAM64' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
        'QAM128' => [
            'changes' => [],
            'uptime' => 0,
            'uptime_human' => null,
        ],
    ];

    public function __construct(string $content, ?Carbon $end = null)
    {
        $this->content = $content;
        $this->end = $end;
        if (!$end) {
            $this->end = Carbon::now();
        }
    }

    public function parse(): SikluUptime
    {
        $result = $this->parseLines();
        $this->start = $result[0]['date'];
        $this->seconds = $this->start->diffInSeconds($this->end);
        $this->period = $this->start->longAbsoluteDiffForHumans(Carbon::now(), 5);
        $this->fillModulationStats($result);
        $this->calculateModulationUptime();

        return $this;
    }

    public function getStart(): string
    {
        return $this->start->toDateTimeString();
    }

    public function getEnd(): string
    {
        return $this->end->toDateTimeString();
    }

    public function getModulations()
    {
        return collect($this->modulations)->where('uptime', '>', 0)->all();
    }

    public function getModulationChanges()
    {
        return collect($this->modulations)->flatMap(function ($modulation, $key) {
            if ($key === 'DOWN') {
                return $modulation['changes'];
            }
        })->groupBy(function ($modulation) {
            return $modulation['start']->toDateString();
        })->map(function ($modulation, $key) {
            $duration = $modulation->sum('duration');

            return [
                'date' => $key,
                'duration' => Carbon::now()->subSeconds($duration)->longAbsoluteDiffForHumans(Carbon::now()),
            ];
        })->values()->all();
    }

    protected function parseLines(): array
    {
        $lines = explode(PHP_EOL, $this->content);
        $result = [];
        foreach ($lines as $line) {
            $result[] = $this->parseLine($line);
        }

        return array_values(array_filter($result));
    }

    protected function fillModulationStats(array $lines)
    {
        foreach ($lines as $key => $line) {
            $start = $line['date'];
            $end = $lines[$key + 1]['date'] ?? $this->end;
            $seconds = $start->diffInSeconds($end);
            $modulation = $line['modulation'];
            if (!$line['modulation'] && $line['link']['interface'] === 'eth0') {
                $modulation = 'down';
            }
            if (in_array($modulation, static::DOWN_MODULATIONS)) {
                $modulation = 'down';
            }
            if ($modulation) {
                $mod = strtoupper($modulation);
                $this->modulations[$mod]['uptime'] += $seconds;
                $this->modulations[$mod]['changes'][] = [
                    'start' => $start,
                    'end' => $end,
                    'duration' => $seconds,
                    'duration_human' => $start->longAbsoluteDiffForHumans($end),
                    'modulation' => $mod,
                ];
            }
        }
    }

    protected function calculateModulationUptime()
    {
        $now = Carbon::now();
        foreach ($this->modulations as $key => $modulation) {
            if ($modulation['uptime']) {
                $this->modulations[$key]['uptime_human'] = $now->copy()->subSeconds($modulation['uptime'])->longAbsoluteDiffForHumans($now, 5);
                $this->modulations[$key]['uptime_percentage'] = round($modulation['uptime'] * 100 / $this->seconds, 4);
            }
        }
    }

    protected function parseLine(string $line): ?array
    {
        $date = $this->extractDate($line);
        $modulation = $this->extractModulation($line);
        $linkStatus = $this->extractLinkStatus($line);
        if (!$modulation && !$linkStatus) {
            return null;
        }

        return [
            'date' => $date,
            'modulation' => $modulation,
            'link' => $linkStatus,
            'line' => $line,
        ];
    }

    protected function extractDate(string $line): ?Carbon
    {
        $search = ['cad:', 'modemd:'];
        foreach ($search as $key) {
            if (Str::contains($line, $key)) {
                $text = Str::before($line, $key);

                return Carbon::createFromFormat('Y M j H:i:s', trim($text));
            }
        }

        return null;
    }

    protected function extractModulation(string $line): ?string
    {
        if (!Str::contains($line, 'modulation change')) {
            return null;
        }

        return Str::afterLast($line, ' ');
    }

    protected function extractLinkStatus(string $line): ?array
    {
        if (!Str::contains($line, ['link down eth eth0'])) {
            return null;
        }

        return [
            'interface' => Str::afterLast($line, ' '),
            'up' => Str::contains($line, 'link up eth')
        ];
    }
}
