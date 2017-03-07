@extends("layouts.master");
@section("title","Product View");
@section("content")
    @for($i=0; $i<count($products); $i++)
        @if(count($failedConditions[$i]) > 0)
            <del title="{{{ var_export($products[$i]->getAttributes()) }}} fails {{implode(",",$failedConditions[$i])}}">
                {{ $products[$i]->RecordReference }}
            </del>
        @else
            <b>{{ $products[$i]->RecordReference }}</b>
        @endif
    @endfor
@stop