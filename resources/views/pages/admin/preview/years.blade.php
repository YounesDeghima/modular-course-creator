@extends('layouts.admin-base')

@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection


@section('main')
    <div class="blocks-container">
        <div class="blocks">
            <div class="block">
                <div class="block-top">
                    <form action="{{route('admin.preview.courses','1')}}" method="get">
                        <h3>year1</h3>
                        <input type="hidden" value="none" name="branch">
                        <input type="submit" value="view courses" class="value-input">
                    </form>

                </div>
            </div>


            <div class="block">
                <div class="block-top">
                    <form action="{{route('admin.preview.courses','2')}}" method="get">
                        <h3>year2</h3>
                        <div class="info-row">
                            <label for="branch" class="branch-label">branch</label>
                            <select name="branch" class="value-input">
                                <option value="mi" >mi</option>
                                <option value="st">st</option>
                            </select>
                        </div>

                        <input type="submit" value="view courses" class="value-input">
                    </form>

                </div>
            </div>


            <div class="block">
                <div class="block-top">
                    <form action="{{route('admin.preview.courses','3')}}" method="get">
                        <h3>year3</h3>
                        <div class="info-row">
                            <label for="branch" class="branch-label">branch</label>
                            <select name="branch" class="value-input">
                                <option value="mi" >mi</option>
                                <option value="st">st</option>
                            </select>
                        </div>

                        <input type="submit" value="view courses" class="value-input">
                    </form>

                </div>
            </div>

    </div>
@endsection
