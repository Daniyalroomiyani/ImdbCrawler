@extends('layout')

@section('content')

    <main id="main">

<!-- ======= usage Section ======= -->
<section id="usage" class="usage" style="margin-left: 15%">
    <div class="container">


        <div class="section-title">
            <h2>send a Crawler</h2>
            <p>
                Simply Fill IMDb ID and Type of date you want and then it Crawls
            </p>
        </div>

        <div class="row" data-aos="fade-in">

            <div class="col-md-9">

                @include('error')
                <form method="post" action="">
                    {{ csrf_field()  }}

                    <div class="row mb-3">
                        <label for="id" class="col-sm-2 col-form-label">IMDb-ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="id" id="id">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="type" class="col-sm-2 col-form-label">Type</label>
                        <div class="col-sm-10">
                            <select name="type" class="form-select" aria-label="Default select example">

                                <option selected value="series">Series</option>

                            </select>
                        </div>
                    </div>


                    <div class="d-grid gap-2 col-6 mx-auto">

                        <button class="btn btn-secondary" type="submit">send</button>
                    </div>
                </form>

            </div>

            <div class="col-lg-7 mt-5 mt-lg-0 d-flex align-items-stretch">
            </div>

        </div>

    </div>
</section><!-- End usage Section -->
    </main>

@endsection
