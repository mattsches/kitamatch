@extends('layouts.app')

@section('content')

<script>
  $(document).ready( function () {
    $('#programs').DataTable( {
            "language": {
                "url": "dataTables.german.lang"
            }
        } );
  } );
</script>

<div class="col-md-8 order-md-1" >
    <h4>List of Programs</h4>

    <table class="table" id="programs">
      <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Capacity</th>
            <th>Address</th>
            <th>Kind</th>
            <th>Coordination</th>
            <th>Status</th>
            <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        @foreach($programs as $program)
            <tr>
                <td>{{$program->pid}}</td>
                <td><a href="/preference/program/{{$program->pid}}">{{$program->name}}</a></td>
                <td>{{$program->capacity}}</td>
                <td>{{$program->address}}</td>
                <td>{{$program->p_kind}}</td>
                <td>{{$program->coordination}}</td>
                <td>{{$program->status}}</td>
                <td>
                    <form action="/program/{{ $program->pid }}" method="POST">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}

                        <button>Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
      </tbody>
    </table>
</div>

@endsection
