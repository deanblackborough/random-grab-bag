<?php
declare(strict_types=1);

use \PhpOffice\PhpSpreadsheet;

namespace DBlackborough\GrabBag;

/**
 * Crawl an Excel spreadsheet and look for tables of data
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/random-grab-bag/blob/master/LICENSE
 */
class ExcelCrawler
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Worksheet
     */
    private $sheet = null;

    /**
     * @var \PhpOffice\PhpSpreadsheet\Reader\Excel2007
     */
    private $reader = null;

    /**
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    private $spreadsheet = null;

    /**
     * @var array Our data
     */
    private $data = [];

    /**
     * @var array Clean grids data array
     */
    private $grids = [];

    /**
     * @var integer The highest row with data in the current sheet
     */
    private $highest_row = 0;

    /**
     * @var array Origin references for left most cells in a grid
     * @todo Need a better solution than this for very large spreadsheets
     */
    private $origins = [];

    /**
     * @var null|string Previous origin value
     */
    private $previous_origin;

    /**
     * ExcelParser constructor.
     *
     * @param string $reader_type
     */
    public function __construct(string $reader_type)
    {
        try {
            $this->reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($reader_type);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        $this->reader->setReadDataOnly(true);
    }

    /**
     * Reset properties
     *
     * @return void
     */
    private function reset()
    {
        $this->data = [];
        $this->grids = [];
        $this->highest_row = 0;
        $this->origins = [];
        $this->previous_origin = null;
    }

    /**
     * Attempt to load the spreadsheet
     *
     * @param string $filename File to load
     *
     * @return ExcelCrawler
     */
    public function load(string $filename) : ExcelCrawler
    {
        $this->spreadsheet = null;

        try {
            $this->spreadsheet = $this->reader->load(getcwd() . $filename);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }

        $this->reset();

        return $this;
    }

    /**
     * @param string $sheet
     *
     * @return ExcelCrawler
     */
    public function loadSheet(string $sheet) : ExcelCrawler
    {
        $this->sheet = null;

        if ($this->spreadsheet !== null && $this->spreadsheet->sheetNameExists($sheet) === true) {
            $this->sheet = $this->spreadsheet->getSheetByName($sheet);
        }

        if ($this->sheet === null) {
            echo 'Unable to load the requested sheet: ' . $sheet;
            exit();
        }

        $this->reset();

        return $this;
    }

    /**
     * Crawl the spreadsheet and create a single data array with arrays for each 'grid'
     *
     * @return ExcelCrawler
     */
    public function crawl() : ExcelCrawler
    {
        $this->highest_row = $this->sheet->getHighestRow();

        for ($row = 1; $row <= $this->highest_row; $row++) {

            $this->previous_origin = null;

            $highest_column = $this->sheet->getHighestColumn($row);
            if (strlen($highest_column) === 1) {
                foreach(range('A', $highest_column) as $col) {
                    $this->assignValue($col, $row);
                }
            } else if (strlen($highest_column) > 1) {
                echo 'Sorry, I cannot crawl a sheet that goes to column AA or beyond, fix me!';
                exit();
            } else {
                $this->assignValue('A', $row);
            }

            $this->previous_origin = null;

        }

        return $this;
    }

    /**
     * Return the generated $this->>data array without modifications
     *
     * @return array
     */
    public function raw() : array
    {
        return $this->data;
    }

    /**
     * Return a simplified data array, just the values organised by grid and row
     *
     * @return array
     */
    public function toArray() : array
    {
        $grids = [];

        $i_grid = 1;
        foreach ($this->data as $grid) {
            $i_row = 1;
            foreach ($grid as $row) {
                foreach ($row as $cell) {
                    $grids[$i_grid][$i_row][] = $cell['value'];
                }
                $i_row ++;
            }
            $i_grid++;
        }

        return $grids;
    }

    /**
     * Return a json array
     *
     * @return string
     */
    public function toJson()
    {
        $grids = [];

        $i_grid = 1;
        foreach ($this->data as $grid) {
            $i_row = 1;
            foreach ($grid as $row) {
                $i_cell = 1;
                foreach ($row as $cell) {
                    $grids['table_' . $i_grid]['row_' . $i_row]['cell_' . $i_cell] = $cell['value'];
                    $i_cell++;
                }
                $i_row++;
            }
            $i_grid++;
        }



        return json_encode($grids);
    }

    /**
     * Assign the cell value to the correct 'grid'
     *
     * @param string $col
     * @param integer $row
     *
     * @return mixed|null|string
     */
    private function assignValue(string $col, int $row)
    {
        $value = $this->cellValue($col, $row);

        if ($value !== null) {
            if (count($this->data) === 0) {
                // No existing data, start a new 'grid'
                $this->data[$col . $row][$row][] = $this->cellArray($col, $row, $value);
                $this->previous_origin = $col . $row;
                $this->origins[$this->previous_origin] = $this->previous_origin;
            } else if ($this->previous_origin !== null) {
                // Continuing a row, add to the existing grid
                $this->data[$this->previous_origin][$row][] = $this->cellArray($col, $row, $value);
            } else {
                // We check the cell above and above and to the right to see if we are within an existing grid
                $above_or_above_right = null;
                if ($row > 1) {
                    $above_or_above_right = $this->cellValue($col, ($row-1));

                    // Try above and right, catches the layout shown below
                    //
                    //   # # #
                    // # # # #
                    // # # # #
                    if ($above_or_above_right === null) {

                        $current_col = $col;
                        $next_col = ++$current_col;

                        // Get the value from above and to the right
                        $above_or_above_right = $this->cellValue($next_col, ($row-1));

                        if ($above_or_above_right !== null) {

                            if (array_key_exists($next_col . ($row-1), $this->data) === true) {

                                /// Move this into a private method when the code for 281 else is complete

                                // Get what is the title row and assign a space to row
                                $title_row = $this->data[$next_col . ($row - 1)];
                                $this->data[$col . ($row - 1)][($row - 1)][] = $this->cellArray($col, ($row - 1), '');

                                // Append the title row cells to the new row
                                foreach ($title_row as $cells) {
                                    foreach ($cells as $cell) {
                                        $this->data[$col . ($row - 1)][($row - 1)][] = $cell;
                                    }
                                }

                                // Clear the old title row from the data array
                                unset($this->data[$next_col . ($row - 1)]);

                                // Set a new origins value for the top right corner and add a new origin
                                $this->origins[$col . ($row - 1)] = $col . ($row - 1);
                                unset($this->origins[$next_col . ($row - 1)]);
                            } else {
                                // We have more than a single nook, need to repeat the above n times

                                # # # #
                                # # # #
                                # # # # # <- Fails here
                                # # # # #

                                // Find the title row and move it over and enter new origin/clean up as per
                                // lines 264-280

                                // Move/cleanup the rows we jumped to get to the 'title' row
                            }
                        }
                    }
                }

                if ($above_or_above_right !== null) {
                    if (array_key_exists($col . ($row-1), $this->origins) === true) {
                        // Fetch the origin cell for the cell above and ad the same value for this new row
                        $this->origins[$col . $row] = $this->origins[$col . ($row-1)];
                        $this->previous_origin = $this->origins[$col . ($row-1)];
                        $this->data[$this->previous_origin][$row][] = $this->cellArray($col, $row, $value);
                    } else {
                        // This should not be called?, only here in case key does not exists inside origin array
                        $this->data[$col . $row][$row][] = $this->cellArray($col, $row, $value);
                        $this->previous_origin = $col . $row;
                        $this->origins[$this->previous_origin] = $this->previous_origin;
                    }
                } else {
                    // Start a new grid
                    $this->data[$col . $row][$row][] = $this->cellArray($col, $row, $value);
                    $this->previous_origin = $col . $row;
                    $this->origins[$this->previous_origin] = $this->previous_origin;
                }
            }
        } else {
            /**
             * No value in the cell, clear the previous origin, we are either going to enter a new grid
             * or start a new row and need to check above and to the right
             */
            $this->previous_origin = null;
        }

        return $this->previous_origin;
    }

    /**
     * Return a cell array
     *
     * @param string $col
     * @param integer $row
     * @param mixed $value
     *
     * @return array
     */
    private function cellArray(string $col, int $row, $value)
    {
        return [
            'col' => $col,
            'row' => $row,
            'cell' => $col . $row,
            'value' => $value
        ];
    }

    /**
     * Fetch the current cell value
     *
     * @param string $col Cell column
     * @param integer $row Cell row
     *
     * @return string|null
     */
    private function cellValue(string $col, int $row)
    {
        try {
            $cell = $this->sheet->getCell($col . $row);
            $value = $cell->getValue();
        } catch (\Exception $e) {
            $value = null; // Ignore the error
        }

        return $value;
    }
}
