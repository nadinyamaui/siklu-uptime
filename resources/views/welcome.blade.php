@extends('layout')

@section('body')
    <p class="lead mb-4">
        Copy the contents from the History Log from your antenna in the text area and click on process. The system will provide an overview of the uptime % of your link
    </p>
    <form action="{{ url('/') }}" method="post">
        @csrf
        <div class="mb-3">
            <label for="history_log" class="form-label">History Log</label>
            <textarea name="history_log"
                      rows="5"
                      class="form-control" id="history_log"></textarea>
        </div>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <button type="submit"
                    class="btn btn-primary btn-lg px-4 gap-3">Calculate Uptime
            </button>
        </div>
    </form>
@endsection
