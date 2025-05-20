<?php
// search for the right document in the documents array
// This assumes we have only 1 sourceoffundspic per user
foreach($documents as $key => $document) {
    if($document['tag'] == 'sourceoffundspic') {
        break;
    }
}
$document = $documents[$key];


?>

@foreach($document['historical_source_of_funds_data'] as $key => $historical_source_of_funds_data)
    <div class="modal fade" id="source_of_funds_modal_{{$key}}" tabindex="-1" role="dialog"
         aria-labelledby="source_of_funds_modal_label" aria-hidden="true">
        <div class="modal-dialog source_of_funds_dialog">
            <div class="modal-content">
                <div class="modal-header source_of_funds_modal_header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Close</span>
                    </button>
                    <h4 class="modal-title" id="replace_file_modal_title"></h4>
                </div>

                <!--begin form-->
                <?php
                $fc = new FormerCommon();

                // Set up initial values for the form
                $full_name   = $historical_source_of_funds_data['name_of_account_holder'];
                $address     = $historical_source_of_funds_data['address'];
                $date        = date_parse_from_format("Y-m-d", $historical_source_of_funds_data['date_of_submission']);
                $day         = $date['day'];
                $month       = $date['month'];
                $year        = $date['year'];
                $document_id = $document['id'];
                $user_id     = $document['user_id'];

                $funding_methods = [];
                if(!empty($historical_source_of_funds_data['funding_methods'])) {
                    $funding_methods       = explode(', ', $historical_source_of_funds_data['funding_methods']);
                }
                
                $others                = $historical_source_of_funds_data['other_funding_methods'];
                $occupation            = $historical_source_of_funds_data['occupation'];
                $annual_income         = $historical_source_of_funds_data['annual_income'];
                $no_income_explanation = $historical_source_of_funds_data['no_income_explanation'];
                $name                  = $historical_source_of_funds_data['name'];
                $your_savings          = $historical_source_of_funds_data['your_savings'];
                $password              = '*******';

                $currency              = $historical_source_of_funds_data['currency'];
                $symbol                = phive('Currencer')->getCurSym($currency);

                // get the correct set of income options based on what the user once selected,
                // he might have changed currency since
                $all_annual_income_options = [
                    'default' => [
                        'annual_income_1' => "{$symbol}0-20,000",
                        'annual_income_2' => "{$symbol}20,000-40,000",
                        'annual_income_3' => "{$symbol}40,000-60,000",
                        'annual_income_4' => "{$symbol}60,000-80,000",
                        'annual_income_5' => "{$symbol}80,000-100,000",
                        'annual_income_6' => "{$symbol}100,000+"
                    ],
                    'other' => [
                        'annual_income_1' => '0-200,000kr',
                        'annual_income_2' => '200,000-400,000kr',
                        'annual_income_3' => '400,000-600,000kr',
                        'annual_income_4' => '600,000-800,000kr',
                        'annual_income_5' => '800,000-1,000,000kr',
                        'annual_income_6' => '1,000,000kr +'
                    ],
                ];

                $your_savings_options_all = [
                    'default' => [
                        'your_savings_1' => "{$symbol}0-25,000",
                        'your_savings_2' => "{$symbol}25,000-50,000",
                        'your_savings_3' => "{$symbol}50,000-100,000",
                        'your_savings_4' => "{$symbol}100,000-250,000",
                        'your_savings_5' => "{$symbol}250,000-500,000",
                        'your_savings_6' => "{$symbol}500,000+"
                    ],
                    'other' => [
                        'your_savings_1' => '0-250,000kr',
                        'your_savings_2' => '250,000-500,000kr',
                        'your_savings_3' => '500,000-1.000,000kr',
                        'your_savings_4' => '1.000,000-2.500,000kr',
                        'your_savings_5' => '2.500,000-5,000,000kr',
                        'your_savings_6' => '5,000,000kr +'
                    ],
                ];

                // Show income options based on the users currency, not the country
                // TODO: this now assumes only SEK, NOK, and DKK have a mod10 currency
                $annual_income_options = $all_annual_income_options['default'];
                if(in_array($currency, ['SEK', 'NOK', 'DKK'])) {
                    $annual_income_options = $all_annual_income_options['other'];
                }

                $your_savings_options = $your_savings_options_all['default'];
                if(in_array($currency, ['SEK', 'NOK', 'DKK'])) {
                    $your_savings_options = $your_savings_options_all['other'];
                }

                // If we ever change the form, make a new version and keep the old version.
                // In the BO we link to the correct version automaticly.
                // The Dmapi keeps track of which version had been filled in by which user.
                $current_version          = $historical_source_of_funds_data['form_version'];
                if(empty($current_version)) {
                    $current_version          = '1.0';
                }
                $version_filepath = getenv('VIDEOSLOTS_PATH') . "/phive/modules/DBUserHandler/html/source_of_funds_form_version_{$current_version}.php";

                $submit_function_name = 'submitSourceOfFundsAsAdmin';

                // check permission to see if the admin can submit
                $can_submit = false;

                require $version_filepath;
                ?>

            </div>
        </div>
    </div>

    <script type="text/javascript">
            $(document).ready(function() {
                @foreach($funding_methods as $funding_method)
                    $("#source_of_funds_modal_{{$key}}").find("#{{$funding_method}}").prop("checked", true);
                @endforeach

                @foreach($annual_income_options as $key2 => $annual_income_option)
                    @if($annual_income == $annual_income_option)
                        $("#source_of_funds_modal_{{$key}}").find("#{{$key2}}").prop("checked", true);
                    @endif
                @endforeach

                @foreach($your_savings_options as $key3 => $your_savings_option)
                    @if($your_savings == $your_savings_option)
                        $("#source_of_funds_modal_{{$key}}").find("#{{$key3}}").prop("checked", true);
                    @endif
                @endforeach
            });

    </script>
@endforeach

    





