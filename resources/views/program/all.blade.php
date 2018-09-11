@extends('layouts.app')

@section('content')

<script>
  $(document).ready( function () {
    $('#programs').DataTable( {
      "pageLength": 50,
      "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.19/i18n/German.json"
            },
    }
         );
  } );
</script>
<div class="row justify-content-center">
<div class="col-md-8">
<h2>Liste aller Kitas -

</h2>
</div>
</div>
<form action="/program/generate/coordinated" method="POST">
  {{ csrf_field() }}
  <button type="button" class="btn btn-outline-secondary" type="submit">Koordinierte Präferenzen erstellen</button>
</form>

<div class="row justify-content-center">
<div class="col-md-12  my-3 p-3 bg-white rounded box-shadow">
    <table class="table" id="programs">
      <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Freie Plätze</th>
            <th>Adresse</th>
            <th>PLZ</th>
            <th>Öffentlich / Frei</th>
            <th>Koordinierung</th>
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
                <td>{{$program->plz}}</td>
                <td>{{$program->p_kind_description}}</td>
                <td>{{$program->coordination_description}}</td>
                <td>{{$program->status_description}}</td>
                <td>
                    <form action="/program/{{ $program->pid }}" method="POST">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}

                        <button>Löschen</button>
                    </form>
                </td>
            </tr>
        @endforeach
      </tbody>
    </table>
</div>
</div>

@endsection
