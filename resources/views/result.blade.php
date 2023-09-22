@extends('layout')

@section('body')
    <p class="lead mb-4">
        Your link log starts on {{ $siklu->getStart() }} and ends
        on {{ $siklu->getEnd()  }}
    </p>
    <p class="lead mb-4">
        Here is an overview of your uptime per modulation
    </p>
    <table class="table">
        <thead>
        <th></th>
        @foreach($modulations as $key => $modulation)
            <th>{{ $key }}</th>
        @endforeach
        </thead>
        <tbody>
        <tr>
            <td>Time (S)</td>
            @foreach($modulations as $key => $modulation)
                <td>{{ $modulation['uptime'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td>Time (Formatted)</td>
            @foreach($modulations as $key => $modulation)
                <td>{{ $modulation['uptime_human'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td>%</td>
            @foreach($modulations as $key => $modulation)
                <td>{{ $modulation['uptime_percentage'] }}</td>
            @endforeach
        </tr>
        </tbody>
    </table>

    <p class="lead mb-4">
        Here is an overview of times when the services went down
    </p>
    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Day</th>
            <th>Duration</th>
        </tr>
        </thead>
        <tbody>
        @foreach($changes as $key => $change)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $change['date'] }}</td>
                <td>{{ $change['duration'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
