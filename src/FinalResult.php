<?php
include 'Constant.php';

class FinalResult {
    /**
     * read file csV and parse data
     * @input:  String fileName
     * @return  array[
            "filename" => "add",
            "document" => "",
            "failure_code" => 0,
            "failure_message" => "",
            "records" => array()
            ]
    **/
    function results($fileName): array
    {
        $dataCsv = $this->readCsv($fileName);
        //check data
        if (!$dataCsv["header"]) {
            return [];
        }
        /** @var Array $header */
        $header = $dataCsv["header"];
        $failureCode = $header[1];
        $failureMessage = $header[2];
        return [
            "filename" => basename($fileName),
            "document" => "",
            "failure_code" => $failureCode,
            "failure_message" => $failureMessage,
            "records" => $dataCsv["records"]
        ];
    }

    /**
     * parse data
     * @input:  array Data
     * @return  array[[
     * "amount" => [
     * "currency" => "VND",
     * "subunits" => 100
     * ],
     * "bank_account_name" => ACB,
     * "bank_account_number" => 123,
     * "bank_branch_code" => ABC,
     * "bank_code" => bankCode,
     * "end_to_end_id" => endToAndId ]]
     **/
    function parseData(string $currency, array $data): array
    {
        $recordData = array();
        //check total column of row = total column of CSV
        if (count($data) == TOTAL_COLUMN) {
            $amt = !$data[8] || "0" == $data[8] ? 0 : (float)$data[8];
            $bankAccNumber = !$data[6] ? BANK_ACC_NUMBER_MISS : (int)$data[6];
            $bankBranchCode = !$data[2] ? BANK_BRANCH_CODE_MISS : $data[2];
            $bankAccountName = str_replace(" ", "_", strtolower($data[7]));
            $end2AndId = !$data[10] && !$data[11] ? END_2_END_ID_MISS : $data[10] . $data[11];
            $bankCode = $data[0];
            $recordData = [
                "amount" => [
                    "currency" => $currency,
                    "subunits" => (int)($amt * UNIST)
                ],
                "bank_account_name" => $bankAccountName,
                "bank_account_number" => $bankAccNumber,
                "bank_branch_code" => $bankBranchCode,
                "bank_code" => $bankCode,
                "end_to_end_id" => $end2AndId,
            ];
        }
        return $recordData;
    }

    /**
     * read file csV
     * @input:  String $file,
     *          int $length,
     *          string $delimiter
     * @return  array[
     * "header" => array[]
     * "values" => array[]
     * ]
     **/
    function readCsv(string $file, int $length = ROW_LENGTH, string $delimiter = DELIMITER): array
    {
        //open file
        $handle = fopen($file, 'r');
        $dataResult = [];
        //check can open file
        if (!$handle) {
            error_log("Can't open file:  " + $file);
            return $dataResult;
        }

        $header = fgetcsv($handle, $length, $delimiter);
        //check have data
        if (!$header) {
            error_log("file:  " + $file + " data empty");
            return $dataResult;
        }
        $currency = $header[0];
        $dataResult["header"] = $header;
        //read data from CSV
        $records = [];
        $row = 1;
        while (false !== ($data = fgetcsv($handle, $length, $delimiter))) {
            $accountInfo = $this->parseData($currency, $data);
            if (empty($accountInfo)) {
                error_log("Row" + $row + "Data Error");
            }
            $records[] = $accountInfo;
            $row ++;
        }
        //system resource recovery.
        fclose($handle);
        $dataResult["header"] = $header;
        $dataResult["records"] = $records;
        return $dataResult;
    }

    function old_results($f) {
        //Variable names have no meaning, making it difficult to read and understand the code.
        //miss add log for tracking bug
        $d = fopen($f, "r");
        $h = fgetcsv($d);
        $rcs = [];
        while(!feof($d)) {
            $r = fgetcsv($d);
            //hard code is difficult for maintain
            if(count($r) == 16) {
                $amt = !$r[8] || $r[8] == "0" ? 0 : (float) $r[8];
                //hard code is difficult for maintain
                $ban = !$r[6] ? "Bank account number missing" : (int) $r[6];
                //hard code is difficult for maintain
                $bac = !$r[2] ? "Bank branch code missing" : $r[2];
                $e2e = !$r[10] && !$r[11] ? "End to end id missing" : $r[10] . $r[11];
                $rcd = [
                    "amount" => [
                        "currency" => $h[0],
                        //hard code is difficult for maintain
                        "subunits" => (int) ($amt * 100)
                    ],
                    "bank_account_name" => str_replace(" ", "_", strtolower($r[7])),
                    "bank_account_number" => $ban,
                    "bank_branch_code" => $bac,
                    "bank_code" => $r[0],
                    "end_to_end_id" => $e2e,
                ];
                $rcs[] = $rcd;
            }
        }
        //it is not necessary because there is no filter  condition which leads to slow running
        $rcs = array_filter($rcs);
        //system resource recovery.
        //miss call fclose($d); it will be consuming resources
        return [
            "filename" => basename($f),
            "document" => $d,
            "failure_code" => $h[1],
            "failure_message" => $h[2],
            "records" => $rcs
        ];
    }
}