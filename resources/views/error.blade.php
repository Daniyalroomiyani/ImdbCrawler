@if($errors->any())
    <div class="alert  panel panel-danger alert-danger text-center  "style=" text-align: center;background-color: rgb(135,39,39);color: aliceblue">
        @foreach($errors->all() as $error)
            <p style="color: whitesmoke">{{ $error }}</p>
        @endforeach
    </div>
@endif
