@extends('layouts.app')

@section('content')

<script>
  $(document).ready( function () {
    $('#availableApplicantsTable').DataTable( {
      /*"aaSorting": []*/
      "pageLength": 100
    } );
  } );
</script>

<div class="panel-body">
  @if (count($availableApplicants) == 0)
  <div class="alert alert-warning" role="alert">
    Right now there are no available applicants for your program.
  </div>
  @endif

  <div class="row justify-content-center">
      <div class="col-md-12">

    <h4>Program {{$program->name}} - uncoordinated process</h4>

    <h6>Capacity: {{$program->openOffers}}/{{$program->capacity}}</h6>

    <br>

    <h3>Offers</h3>

    <table class="table" id="offers">
      <thead>
          <tr>
              <th>ID</th>
              <th>First name</th>
              <th>Last name</th>
              <th>Birthday</th>
              <th>Gender</th>
          </tr>
      </thead>
      <tbody>
        @foreach($availableApplicants as $applicant)
            @if (array_key_exists($applicant->aid, $offers) && $offers[$applicant->aid]['id'] > 0 && $offers[$applicant->aid]['rank'] == 1)
              @if ($applicant->status == 26)
                <tr class="table-success">
                  <th scope="row"><a target="_blank" href="/preference/applicant/{{$applicant->aid}}">{{$applicant->aid}}</a></th>
                  <td>{{$applicant->first_name}}</td>
                  <td>{{$applicant->last_name}}</td>
                  <td>{{(new Carbon\Carbon($applicant->birthday))->format('d.m.Y')}}</td>
                  <td>{{$applicant->gender}}</td>
                </tr>
              @elseif ($offers[$applicant->aid]['rank'] == 1)
              <tr class="table-info">
                <th scope="row"><a target="_blank"  href="/preference/applicant/{{$applicant->aid}}">{{$applicant->aid}}</a></th>
                <td>{{$applicant->first_name}}</td>
                <td>{{$applicant->last_name}}</td>
                <td>{{(new Carbon\Carbon($applicant->birthday))->format('d.m.Y')}}</td>
                <td>{{$applicant->gender}}</td>
              </tr>
              @endif
            @endif
        @endforeach
      </tbody>
    </table>

</div></div>
    <hr class="mb-4">

    <div class="row justify-content-center">
        <div class="col-md-12">

    <h3>Waitlist</h3>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $("input[name=_token]").val()
            }
        });

        $(function() {
          $('#sortable').sortable({
            axis: 'y',
            update: function (event, ui) {
              $("span.rank").text(function() {
                return $(this).parent().index("tr")+1;
              });
              var order = $(this).sortable('serialize');
              var _token = $("input[name=_token]").val();
              var data = {"order": order, "_token": _token};
              $.ajax({
                data: data,
                type: 'POST',
                url: '/preference/program/uncoordinated/reorder/pid}}',
                success: function(data) {
                  console.log(data);
                }
              });
            }
          })
            $( "#sortable" ).disableSelection();
        });
    </script>

    /preference/program/uncoordinated/reorder/{pID}

    <table class="table" id="waitlist">
      <thead>
          <tr>
              <th>ID</th>
              <th>First name</th>
              <th>Last name</th>
              <th>Birthday</th>
              <th>Gender</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
          </tr>
      </thead>
      <tbody id="sortable">
        @foreach($availableApplicants as $applicant)
          @if (array_key_exists($applicant->aid, $offers) and $offers[$applicant->aid]['id'] > 0 and $offers[$applicant->aid]['rank'] > 1 and $applicant->status != 26)
          <tr id="item-<?php

            $key = array_search($applicant->aid, array_column($preferences, 'id_to'));
            echo $preferences[$key]['prid'];
            ?>">
            <th scope="row"><a target="_blank"  href="/preference/applicant/{{$applicant->aid}}">{{$applicant->aid}}</a></th>
            <td>{{$applicant->first_name}}</td>
            <td>{{$applicant->last_name}}</td>
            <td>{{(new Carbon\Carbon($applicant->birthday))->format('d.m.Y')}}</td>
            <td>{{$applicant->gender}}</td>
            <td>
                @if ($program->openOffers != $program->capacity)
                <form action="/preference/program/uncoordinated/upoffer" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="prid" value="{{$offers[$applicant->aid]['id']}}">
                    <button>Offer</button>
                </form>
                @endif
            </td>
            <td>
                @if ($offers[$applicant->aid]['id'] > 0
                  && $applicant->status != 26
                  && $offers[$applicant->aid]['delete'])
                <form action="/preference/program/uncoordinated/{{$offers[$applicant->aid]['id']}}" method="POST">
                  {{ csrf_field() }}
                  {{ method_field('DELETE') }}
                  <button>Delete</button>
                </form>
                @endif
            </td>
          </tr>
          @endif
        @endforeach
      </tbody>
    </table>

    <hr class="mb-4">

</div></div>

<div class="row justify-content-center">
    <div class="col-md-12">

    <h3>Available Applicants</h3>

    <table class="table" id="availableApplicantsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>First name</th>
                <th>Last name</th>
                <th>Birthday</th>
                <th>Gender</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @foreach($availableApplicants as $applicant)
            @if ( !(array_key_exists($applicant->aid, $offers) and $offers[$applicant->aid]['id'] != -1) )
            <tr
                  @if (array_key_exists($applicant->aid, $offers))
                    @if ($offers[$applicant->aid]['id'] == -1)
                      class="table-danger"
                    @endif
                  @endif
                  @if ($applicant->status == 26)
                    class="table-danger"
                  @endif
                >
                <th scope="row"><a target="_blank"  href="/preference/applicant/{{$applicant->aid}}">{{$applicant->aid}}</a></th>
                <td>{{$applicant->first_name}}</td>
                <td>{{$applicant->last_name}}</td>
                <td>{{(new Carbon\Carbon($applicant->birthday))->format('d.m.Y')}}</td>
                <td>{{$applicant->gender}}</td>
                <td>
                    <!-- show button, if no -1 or 1 set && capacity is not fullfilled-->
                    @if ($applicant->status == 26)
                        Matched
                    @elseif (!(array_key_exists($applicant->aid, $offers)) && ($program->openOffers != $program->capacity))
                    <form action="/preference/program/uncoordinated/offer/{{$program->pid}}" method="POST">
                        {{ csrf_field() }}
                        <input type="hidden" name="aid" value="{{$applicant->aid}}">
                        <button>Offer</button>
                    </form>
                    @endif
                </td>
                <td>
                    @if ($applicant->status != 26 && !((array_key_exists($applicant->aid, $offers) && $offers[$applicant->aid]['id'] == -1)) )
                    <form action="/preference/program/uncoordinated/waitlist/{{$program->pid}}" method="POST">
                      {{ csrf_field() }}
                      <input type="hidden" name="aid" value="{{$applicant->aid}}">
                      <button>Waitlist</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
</div>

</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <hr class="mb-4">
        <a href="/program/{{$program->pid}}"><button class="btn btn-primary btn-lg btn-block">Back to program</button></a>
        <hr class="mb-4">
        <a href="/criteria/program/{{$program->pid}}"><button class="btn btn-primary btn-lg btn-block">Edit criteria</button></a>
    </div>
</div>

@endsection
