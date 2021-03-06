<?php
namespace Fathilarhm;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GsheetsCollection{
    private $url;
    private $spreadsheet_id;
    private $spreadsheet_sheet;
    private $spreadsheet_json_url;
    private static $spreadsheet_json_url_template = 'https://spreadsheets.google.com/feeds/cells/{SPREADSHEET_ID}/{SPREADHSEET_SHEET}/public/full?alt=json';

    public function __construct($url)
    {
        // Set Spreadsheet URL
        $this->url = $url;

        // Set Spreadsheet Id
        $this->spreadsheet_id = explode('/', $url)[5];

        // Set Spreadsheet Sheet (default = 1)
        $this->spreadsheet_sheet = 1;

        // Set Spreadsheet JSON URL based on Spreadsheet JSON URL rule
        $this->processJsonUrl();
    }

    public static function url($url)
    {
        return (new GsheetsCollection($url));
    }

    public function getProcessedUrl()
    {
        return $this->spreadsheet_json_url;
    }

    public function get($sheet = 1)
    {
        // Set Spreadsheet Sheet
        $this->setSheet($sheet);

        // Spreadsheet data extraction proccess
        $request = Http::get($this->spreadsheet_json_url);
        $data = json_decode($request);

        // Extract the Spreadhseet data
        $data = $data->feed;
        $data = $data->entry;

        // Last Update DateTime
        // $last_update = $data->updated->{'$t'};

        // Get Sheet Columns
        $columns = [];
        foreach ($data as $value) {
            if($value->{'gs$cell'}->row != 1){
                continue;
            }
            $columns[] = $value->{'gs$cell'}->inputValue;
        }

        // Iterate Data & Put Value into Column & Push to Collection
        $result = new Collection();
        $column_counter = 0;
        $row = [];
        $loop_counter = count($columns);
        $row_counter = 2;
        $col_counter = 1;
        while($loop_counter < count($data)){
            if($data[$loop_counter]->{'gs$cell'}->row == $row_counter && $data[$loop_counter]->{'gs$cell'}->col == $col_counter){
                $row[$columns[($col_counter-1)]] = $data[$loop_counter]->{'gs$cell'}->inputValue;
                $loop_counter++;
            }else{
                $row[$columns[($col_counter-1)]] = null;
            }

            $col_counter++;

            if(($col_counter-1) == count($columns)){
                $col_counter = 1;
                $row_counter++;
                $result->push($row);
            }
        }

        return $result;
    }

    private function setSheet($sheet){
        $this->spreadsheet_sheet = $sheet;
        $this->processJsonUrl();
    }

    private function processJsonUrl(){
        $spreadsheet_json_url = self::$spreadsheet_json_url_template;
        $spreadsheet_json_url = str_replace('{SPREADSHEET_ID}', $this->spreadsheet_id, $spreadsheet_json_url);
        $spreadsheet_json_url = str_replace('{SPREADHSEET_SHEET}', $this->spreadsheet_sheet, $spreadsheet_json_url);
        $this->spreadsheet_json_url = $spreadsheet_json_url;
    }
}
