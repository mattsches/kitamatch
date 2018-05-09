@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
      @if ($errors->any())
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
      @endif

      @if ($program->status == 10)
      <div class="alert alert-danger" role="alert">
        Required information missing. Please fullfill your profil.
      </div>
      @endif

      @if ($program->status == 13)
      <div class="alert alert-danger" role="alert">
        You are inactive for at least 7 days.
      </div>
      @endif

        <h2>Edit your Program information</h2>
</div>
</div>
<div class="row justify-content-center">
    <div class="col-md-8 my-3 p-3 bg-white rounded box-shadow">
        <form action="/program/{{$program->pid}}" method="POST">
            {{ csrf_field() }}

            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Name</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="name" name="name" value="{{$program->name}}">
                </div>
            </div>
            <!-- Email but with user-table! -->
            <div class="form-group row">
                <label for="capacity" class="col-sm-2 col-form-label">Capacity</label>
                <div class="col-sm-10">
                  <input type="number" min="1" class="form-control" id="capacity" name="capacity" placeholder="10" value="{{$program->capacity}}">
                </div>
            </div>
            <div class="form-group row">
                <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="phone" name="phone" placeholder="+49123456789" value="{{$program->phone}}">
                </div>
            </div>
            <div class="form-group row">
                <label for="address" class="col-sm-2 col-form-label">Address</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="address" name="address" placeholder="1234 Main St" value="{{$program->address}}">
                </div>
            </div>
            <div class="form-group row">
                <label for="plz" class="col-sm-2 col-form-label">PLZ</label>
                <div class="col-sm-3">
                  <input type="text" class="form-control" id="plz" name="plz" placeholder="12345" value="{{$program->plz}}">
                </div>
                <label for="city" class="col-sm-2 col-form-label">City</label>
                <div class="col-sm-5">
                  <input type="text" class="form-control" id="city" name="city" placeholder="City" value="{{$program->city}}">
                </div>
            </div>
            @if ($program->p_kind == 2)
            <div class="form-group row">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-8">
                    {{ Form::checkbox('coordination', 1, $program->coordination, ['class' => 'form-check-input', 'id' => 'coordination']) }}
                    <label class="form-check-label" for="coordination">
                        Coordination
                    </label>
                </div>
            </div>
            @endif

            <hr class="mb-4">
            <button class="btn btn-primary btn-lg btn-block" type="submit">Update</button>
        </form>
    </div>
</div>

<br>

<div class="row justify-content-center">
    <div class="col-md-8">
        @if ($program->coordination == 1)
            <a href="/preference/program/{{$program->pid}}"><button class="btn btn-primary btn-lg btn-block">See preferences</button></a>
        @else
            <a href="/preference/program/{{$program->pid}}"><button class="btn btn-primary btn-lg btn-block">Make offers</button></a>
        @endif

        <!-- to do: add provider button if it has a provider-->
        @if ($program->proid)
        <hr class="mb-4">
        <a href="/provider/{{$program->proid}}"><button class="btn btn-primary btn-lg btn-block">Back to provider</button></a>
        @endif
    </div>
</div>

@endsection
