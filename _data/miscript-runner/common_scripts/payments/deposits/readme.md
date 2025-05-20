# Crediting Deposits

There are deposits which were successful at the psp level, but mts was not updated and therefore also the deposit was also not credited at the casino's end either.

In order to manually add deposits please see example script in `script.php` and sample csv file needed for input `deposits_to_credit.csv`.
The script is going to insert an entry to the deposits table and credit the user. Will also update the status and the reference_id of the mts transaction.

Required columns for the initial input.
user_id: user id at the casino
amount: amount to deposit in cents!!
reference_id: psps transaction id
mts_id: our money transaction systems transaction id which is for the psp our id
supplier: lowercase supplier
sub_supplier: lowercase sub_supplier when applicable

If there is no mts_id added the supplier and if applicable the sub_supplier especially needs to be filled in.

Extra helper functions:
!! These are not mandatory, just here to help for checks if needed.
initial_checks.php
Was created to try to identify the mts_id based on user_id and reference_id in case mts_id is not provided.
Also will check if the deposit already exits or not.
Please see initial_data.csv as sample for this.

post_depoloy_check.php
Can be used to read the output csv of the story and validate if the deposits are credited and if the mts transaction has the right status and reference_id.

All logic is defined in deposits.php and as for running a manual script the required files are in template folder.
These scripts can be run from the story folder. Currently, saving csv to disk is going to the common_scripts/payments folder.
