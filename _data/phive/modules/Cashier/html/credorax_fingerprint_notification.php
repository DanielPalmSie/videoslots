<html>
<head> </head>
<body>
<?php
$isFingerprintDone = false;

if (isset($_POST['threeDSMethodData'])) {
    $decodedString = base64_decode($_POST['threeDSMethodData']);

    if ($decodedString) {
        $parsedData = json_decode($decodedString, true);

        if (isset($parsedData['threeDSServerTransID'])) {
            $isFingerprintDone = true;
        }
    }
}
?>
<script>
    parent.document.credoraxFingerPrintDone = <?php echo json_encode($isFingerprintDone); ?>;
</script>
</body>
</html>
