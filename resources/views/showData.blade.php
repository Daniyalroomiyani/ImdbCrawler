@extends('layout')

@section('content')

    <main id="main">

        <!-- ======= usage Section ======= -->
        <section id="usage" class="usage" style="margin-left: 15%">
            <div class="container">


                <div class="section-title">
                    <h2>

                        result for : {{$imdbid}}
                    </h2>
                </div>

                <div class="row" data-aos="fade-in">

                    <div class="col-md-10" >
                        <json-viewer>
                            {{$result}}
                        </json-viewer>
                    </div>



                </div>

            </div>

        </section><!-- End usage Section -->
        <div class="d-grid gap-2 col-6 mx-auto">
            <a href="{{route('usage')}}" class="btn btn-secondary " >Reset</a>
        </div>
    </main>

@endsection
