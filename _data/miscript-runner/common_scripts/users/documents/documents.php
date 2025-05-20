<?php


/**
 * Add a document for a particular user
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add missing documents
 * @param string $tag     The tag (document) to add if it does not exist - One of the below keys:
 *                        'addresspic'           => 'addresspic',
 *                        'bank'                 => 'bankpic',
 *                        'bankpic'              => 'bankpic',
 *                        'citadel'              => 'citadelpic',
 *                        'creditcard'           => 'creditcardpic',
 *                        'ccard'                => 'creditcardpic',
 *                        'applepay'             => 'creditcardpic',
 *                        'ecopayz'              => 'ecopayzpic',
 *                        'emp'                  => 'creditcardpic',
 *                        'idcard-pic'           => 'idcard-pic',
 *                        'instadebit'           => 'instadebitpic',
 *                        'mb'                   => 'skrillpic',
 *                        'neteller'             => 'netellerpic',
 *                        'skrill'               => 'skrillpic',
 *                        'trustly'              => 'bankaccountpic',
 *                        'paypal'               => 'paypalpic',
 *                        'wirecard'             => 'creditcardpic',
 *                        'worldpay'             => 'creditcardpic',
 *                        'hexopay'              => 'creditcardpic',
 *                        'sourceoffunds'        => 'sourceoffundspic',
 *                        'entercash'            => 'bankaccountpic',
 *                        'internaldocument'     => 'internaldocumentpic',
 *                        'proofofwealth'        => 'proofofwealthpic',
 *                        'proofofsourceoffunds' => 'proofofsourceoffundspic',
 *                        'venuspoint'           => 'venuspointpic',
 *                        'interac'              => 'interacpic',
 *                        'muchbetter'           => 'muchbetterpic',
 *                        'zimplerbank'          => 'bankaccountpic',
 *                        'sepa'                 => 'bankaccountpic',
 *                        'wpsepa'               => 'bankaccountpic',
 *                        'mifinity'             => 'mifinitypic',
 *                        'astropay'             => 'astropaypic',
 *                        'astropaywallet'       => 'astropaypic',
 *                        'credorax'             => 'creditcardpic',
 *                        'sourceofincome'       => 'sourceofincomepic',
 * @return int The number of documents added (0 or 1)
 */
function addMissingDocument(string $sc_id, int $user_id, string $tag): int
{
    $query = "
            SELECT id, user_id, tag, status, created_at
            FROM documents
            WHERE tag = '{$tag}'
                 AND user_id = {$user_id}
                 AND status >= 0
                 AND deleted_at is null";
    $doc = phive('SQL')->doDb('dmapi')->loadArray($query);

    if (empty($doc)) {
        phive('Dmapi')->createEmptyDocument($user_id, $tag, '', '', '', 0, [], 'requested');
        phive('UserHandler')->logAction($user_id, "Manually added {$tag} document - {$sc_id}}", "comment");

        // re-check created doc
        usleep(1000000); // wait 1 second
        $doc = phive('SQL')->doDb('dmapi')->loadArray($query)[0];
        echo "Added document:   id: [{$doc['id']}]  user_id: [{$doc['user_id']}]   tag: [{$doc['tag']}]   created_at: [{$doc['created_at']}]   status: [{$doc['status']}]  \n";
        return 1;
    } else {
        return 0;
    }
}

/**
 * Add any missing idcard-pic/addresspic/bankpic documents for a particular user
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add missing documents
 * @return int The number of documents added
 */
function addMissingDocuments(string $sc_id, int $user_id): int
{
    echo "Adding missing documents for user ID {$user_id}\n";
    $poi_added  = addMissingDocument($sc_id, $user_id, 'idcard-pic');
    $poa_added  = addMissingDocument($sc_id, $user_id, 'addresspic');
    $vba_added  = addMissingDocument($sc_id, $user_id, 'bankpic');
    return $poi_added + $poa_added + $vba_added;
}

/**
 * Adds any missing idcard-pic/addresspic/bankpic document to multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array $user_ids The array of user ids to add missing documents
 */
function addMissingDocumentsMulti(string $sc_id, array $user_ids)
{
    echo "Adding missing documents to multiple users -----\n";
    $total_users = 0;
    $total_documents = 0;
    foreach ($user_ids as $user_id) {
        $total_documents += addMissingDocuments($sc_id, $user_id);
        $total_users++;
    }
    echo "Processed all users - added {$total_documents} document for {$total_users} different users -----\n\n";
}

/**
 * Add missing documents for a particular user
 *
 * @param string  $sc_id    Story Id used in logging and auditing
 * @param integer $user_id  The user id to add missing documents
 * @param string  $card_last_four Last 4 digits of card
 * @return int The number of documents added (0 or 1)
 */
function addMissingDocumentCreditCards(string $sc_id, int $user_id, string $card_last_four): int
{
    $query = "SELECT id, user_id, tag, subtag, status, created_at
              FROM documents
              WHERE tag = 'creditcardpic'
              AND user_id = {$user_id}
              AND subtag like '%{$card_last_four}'
             AND status >= 0
             AND deleted_at is null";
    $doc = phive('SQL')->doDb('dmapi')->loadArray($query);
    if (empty($doc)) {
        $card = phive('SQL')->doDb('mts')->loadAssoc("SELECT *
                                                      FROM credit_cards
                                                      WHERE user_id = {$user_id}
                                                        AND card_num like '%{$card_last_four}'
                                                      ORDER BY id DESC
                                                      LIMIT 1");
        if (empty($card)) {
            echo "WARNING: Card not found for user_id: {$user_id} with last_four: {$card_last_four}!\n";
            return 0;
        }
        $type = phive('WireCard')->getCardType($card['card_num']);
        $extra = array_merge($card, ['type' => $type]);
        phive('Dmapi')->createEmptyDocument($user_id, 'creditcard', '', $card['card_num'], $card['id'], 0, $extra, 'requested');
        phive('UserHandler')->logAction($user_id, "Manually added creditcard document - {$sc_id}}", "comment");

        // re-check created doc
        usleep(1000000); // wait 1 second
        $doc = phive('SQL')->doDb('dmapi')->loadArray($query)[0];
        echo "Added document:   id: [{$doc['id']}]  user_id: [{$doc['user_id']}]   tag: [{$doc['tag']}]   subtag: [{$doc['subtag']}]   created_at: [{$doc['created_at']}]   status: [{$doc['status']}]  \n";
        return 1;
    } else {
        return 0;
    }
}

/**
 * Add missing documents for a particular user
 *
 * @param string  $sc_id       Story Id used in logging and auditing
 * @param array   $user_cards  Array of user - cards of the format [ user_id => last_four ] e.g. [112233 => "1234", 445566 => "5678"]
 */
function addMissingDocumentCreditCardMulti(string $sc_id, array $user_cards)
{
    echo "Adding missing documents Credit Cards to multiple users/cards -----\n";
    $total_users = 0;
    $total_documents = 0;
    foreach ($user_cards as $user_id => $last_four) {
        $total_documents += addMissingDocumentCreditCards($sc_id, $user_id, $last_four);
        $total_users++;
    }
    echo "Processed all users - added {$total_documents} document for {$total_users} different users -----\n\n";
}


/**
 * Remove a document slot for a particular user
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to remove document slot
 * @param string $tag     The tag (document) to remove - One of the below keys:
 *                        'addresspic'           => 'addresspic',
 *                        'bank'                 => 'bankpic',
 *                        'bankpic'              => 'bankpic',
 *                        'citadel'              => 'citadelpic',
 *                        'creditcard'           => 'creditcardpic',
 *                        'ccard'                => 'creditcardpic',
 *                        'applepay'             => 'creditcardpic',
 *                        'ecopayz'              => 'ecopayzpic',
 *                        'emp'                  => 'creditcardpic',
 *                        'idcard-pic'           => 'idcard-pic',
 *                        'instadebit'           => 'instadebitpic',
 *                        'mb'                   => 'skrillpic',
 *                        'neteller'             => 'netellerpic',
 *                        'skrill'               => 'skrillpic',
 *                        'trustly'              => 'bankaccountpic',
 *                        'paypal'               => 'paypalpic',
 *                        'wirecard'             => 'creditcardpic',
 *                        'worldpay'             => 'creditcardpic',
 *                        'hexopay'              => 'creditcardpic',
 *                        'sourceoffunds'        => 'sourceoffundspic',
 *                        'entercash'            => 'bankaccountpic',
 *                        'internaldocument'     => 'internaldocumentpic',
 *                        'proofofwealth'        => 'proofofwealthpic',
 *                        'proofofsourceoffunds' => 'proofofsourceoffundspic',
 *                        'venuspoint'           => 'venuspointpic',
 *                        'interac'              => 'interacpic',
 *                        'muchbetter'           => 'muchbetterpic',
 *                        'zimplerbank'          => 'bankaccountpic',
 *                        'sepa'                 => 'bankaccountpic',
 *                        'wpsepa'               => 'bankaccountpic',
 *                        'mifinity'             => 'mifinitypic',
 *                        'astropay'             => 'astropaypic',
 *                        'astropaywallet'       => 'astropaypic',
 *                        'credorax'             => 'creditcardpic',
 *                        'sourceofincome'       => 'sourceofincomepic',
 * @return int The number of documents removed (0 or 1)
 */

function deleteDocumentSlot(string $sc_id, int $user_id, string $tag): int
{
    $query = "
            SELECT id, user_id, tag, status, created_at
            FROM documents
            WHERE tag = '{$tag}'
                 AND user_id = {$user_id}";
    $doc = phive('SQL')->doDb('dmapi')->loadArray($query);

    if (empty($doc)) {
        echo "Document slot does not exist for user {$user_id} with tag {$tag}\n";
        return 0;
    }
    if (count($doc) > 1) {
        echo "More than one document slot exist for user {$user_id} with tag {$tag}\n";
        return 0;
    }

    phive("SQL")->doDb('dmapi')->query("DELETE FROM documents WHERE user_id = '{$user_id}' AND tag = '{$tag}'");
    phive('UserHandler')->logAction($user_id, "Manually removed {$tag} document slot - {$sc_id}", "comment");

    // re-check deleted doc
    $doc = phive('SQL')->doDb('dmapi')->loadArray($query)[0];
    echo "Removed document:   id: [{$doc['id']}]  user_id: [{$doc['user_id']}]   tag: [{$doc['tag']}]   created_at: [{$doc['created_at']}]   status: [{$doc['status']}]  \n";
    return 1;
}
