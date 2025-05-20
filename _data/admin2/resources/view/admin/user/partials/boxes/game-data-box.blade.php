<?php
/**
 * @var \App\Models\User $user
 */
$box_name = "game-data-box";
$collapse = $_COOKIE["new-bo-$box_name"];
?>

<div class="card card-outline card-warning @if($collapse == 1) collapsed-box @endif" id="{{ $box_name }}">
    <div class="card-header">
        <h3 class="card-title text-lg">Game data -
                <a href="javascript:void(0)" style="color:inherit" id="game-daterange-btn" data-url="{{ $app['url_generator']->generate('admin.user-get-games-data-ajax', ['user' => $user->id]) }}" data-target="#ajax-container-game">
                    <span>All time</span> <i class="far fa-calendar-alt"></i>
                </a>
            </h3>
        <div class="card-tools">
            <button class="btn btn-tool" data-boxname="{{ $box_name }}" id="{{ $box_name }}-btn" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="ajax-container-game">
            @include('admin.user.partials.boxes.game-data', ['start_date' => null, 'end_date' => null])
        </div>
        <div>

        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            manageCollapsible("{{ $box_name }}" + "-btn", 'box');
            manageFilteredData('game-daterange-btn');
        });
    </script>
@endsection




