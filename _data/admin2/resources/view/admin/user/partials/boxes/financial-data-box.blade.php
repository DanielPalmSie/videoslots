<?php
/**
 * @var \App\Models\User $user
 */
?>

<div class="card card-outline card-warning @if($financial_data_collapse == 1) collapsed-box @endif" id="financial-data-box">
    <div class="card-header">
        <h3 class="card-title text-lg text-dark">
            Financial data -
            <a href="javascript:void(0)"
               class="text-dark"
               id="financial-daterange-btn"
               data-url="{{ $app['url_generator']->generate('admin.user-get-financial-data-ajax', ['user' => $user->id]) }}"
               data-target="#ajax-container-financial">
                <span>Last 12 months</span>
                <i class="far fa-calendar-alt"></i>
            </a>
        </h3>
        <div class="card-tools">
            <button class="btn btn-tool"
                    id="financial-data-box-btn"
                    data-boxname="financial-data-box"
                    data-widget="collapse"
                    data-toggle="tooltip"
                    title="Collapse">
                <i class="fas fa-{{ $financial_data_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body" id="ajax-container-financial">
        @include('admin.user.partials.boxes.financial-data', [
            'deposits' => $user->repo->getDepositsList(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now()),
            'withdrawals' => $user->repo->getWithdrawalsList(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now())
        ])
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            manageFilteredData('financial-daterange-btn', true);
        });
    </script>
@endsection
