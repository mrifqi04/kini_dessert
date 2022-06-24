@extends('front.layout.master')

@section('content')
    @include('front.layout.home-banner')

    <div class="pt-5">
        @include('front.layout.popular-products', ['products' => $newest_products])
    </div>

    <hr>

    <div class="container py-5">
        <div class="popular_courses">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="main_title">
                            <h2 class="mb-3">Produk Rekomendasi</h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- single course -->
                    <div class="col-lg-12">
                        <div class="owl-carousel active_course">
                            @if (Auth::user())
                                @foreach ($predictions as $item)
                                    <div class="card_book single_course">
                                        <div
                                            class="course_head d-flex flex-wrap justify-content-center align-content-center">
                                            <a href="{{ $item->product->url_show }}" class="d-block">
                                                <img class="img-fluid" src="{{ $item->product->url_cover }}"
                                                    alt="{{ $item->product->title }}" />
                                            </a>                                           
                                        </div>
                                        <div class="course_content p-3">
                                            <div class="title_heading row">
                                                <h4 class="product_title col-6">
                                                    <a title="{{ $item->product->title }}"
                                                        href="{{ $item->product->url_show }}">{{ $item->product->title }}</a>
                                                </h4>
                                                @if ($item->rating >= 4)
                                                  <div class="col-6 text-right">
                                                      <span class="badge badge-danger pt-1">Terfavorit</span>
                                                  </div>                                                
                                                @endif
                                            </div>
                                            <div class="row">
                                                <div class="col-12 py-2">
                                                    <span>{{ $item->product->price_label }}</span>
                                                </div>
                                                @if (!isset($button_cart) || $button_cart == true)
                                                    <div class="col-12">
                                                        <button title="Add to cart"
                                                            class="btn btn-primary btn-block add-to-cart"
                                                            data-id="{{ $item->product->product_id }}">
                                                            <i class="ti-shopping-cart"></i>
                                                            Add
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                @foreach ($popular_products as $item)
                                    <div class="card_book single_course">
                                        <div
                                            class="course_head d-flex flex-wrap justify-content-center align-content-center">
                                            <a href="{{ $item->url_show }}" class="d-block">
                                                <img class="img-fluid" src="{{ $item->url_cover }}"
                                                    alt="{{ $item->title }}" />
                                            </a>
                                            @if ($item->category_name)
                                                {{-- <div class="product_category">{{ $item->category_name }}</div> --}}
                                            @endif
                                        </div>
                                        <div class="course_content p-3">
                                            <h4 class="product_title col-6">
                                                <a title="{{ $item->title }}"
                                                    href="{{ $item->url_show }}">{{ $item->title }}</a>
                                            </h4>
                                            <div class="row">
                                                <div class="col-12 py-2">
                                                    <span>{{ $item->price_label }}</span>
                                                </div>
                                                @if (!isset($button_cart) || $button_cart == true)
                                                    <div class="col-12">
                                                        <button title="Add to cart"
                                                            class="btn btn-primary btn-block add-to-cart"
                                                            data-id="{{ $item->product_id }}">
                                                            <i class="ti-shopping-cart"></i>
                                                            Add
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
