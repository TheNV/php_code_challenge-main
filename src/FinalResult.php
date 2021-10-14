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
        $failureCode = !$header[FAIL_CODE_COLUMN] ? "" : $header[FAIL_CODE_COLUMN];
        $failureMessage =  !$header[FAIL_MESS_COLUMN] ?  "" : $header[FAIL_MESS_COLUMN];
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
     * @input:  array Data get from CV
     * @return  array[[
     *          "amount" => [
     *          "currency" => "VND",
     *          "subunits" => 100
     *          ],
     *          "bank_account_name" => ACB,
     *          "bank_account_number" => 123,
     *          "bank_branch_code" => ABC,
     *          "bank_code" => bankCode,
     *          "end_to_end_id" => endToAndId ]]
     **/
    function parseData(string $currency, array $data): array
    {
        //check total column of row = total column of CSV
        if (count($data) != TOTAL_COLUMN) {
            return[];
        }

        $amt = !$data[AMT] || "0" == $data[AMT] ? AMT_VALUE_DEFAULT : (float)$data[AMT];
        $bankAccNumber = !$data[BANK_ACC_NUMBER] ? BANK_ACC_NUMBER_MISS : (int)$data[BANK_ACC_NUMBER];
        $bankBranchCode = !$data[BANK_BRANCH_CODE] ? BANK_BRANCH_CODE_MISS : $data[BANK_BRANCH_CODE];
        $bankAccountName = str_replace(" ", "_", strtolower($data[BANK_ACC_NAME]));
        $end2EndId = !$data[END_ID] && !$data[END_ID_2] ? END_2_END_ID_MISS : $data[END_ID] . $data[END_ID_2];
        $bankCode = $data[BANK_CODE];
        return  [
            "amount" => [
                "currency" => $currency,
                "subunits" => (int)($amt * UNITS)
            ],
            "bank_account_name" => $bankAccountName,
            "bank_account_number" => $bankAccNumber,
            "bank_branch_code" => $bankBranchCode,
            "bank_code" => $bankCode,
            "end_to_end_id" => $end2EndId,
        ];

    }

    /**
     * read file csV
     * @input:  String $file, file name
     *          int $length, of row
     *          string $delimiter
     * @return  array[
     *      "header" => array[]
     *      "values" => array[]
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
        $currency = !$header[CURRENCY] ? "" : $header[CURRENCY];
        $dataResult["header"] = $header;
        //read data from CSV
        $records = [];
        $row = 1;
        while (false !== ($data = fgetcsv($handle, $length, $delimiter))) {
            $accountInfo = $this->parseData($currency, $data);
            if (empty($accountInfo)) {
                error_log("Row" + $row + "Data Error");
                break;
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