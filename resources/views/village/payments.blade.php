@extends('layouts.app')

@section('content')
    <div id="collective-payments-app"
         data-initial-sppts='@json($sppts->items())'
         data-search="{{ $search }}"
         data-rt-filter="{{ $rtFilter }}"
         data-rw-filter="{{ $rwFilter }}"
         data-rt-options='@json($rtOptions)'
         data-rw-options='@json($rwOptions)'
         data-batch-url="{{ route('village.payments.batch') }}"
         data-success="{{ session('success') ? 'true' : 'false' }}"
         data-pagination='@json([
             "firstItem" => $sppts->firstItem(),
             "lastItem" => $sppts->lastItem(),
             "total" => $sppts->total(),
             "links" => $sppts->links()->render(),
         ])'
    ></div>
@endsection
