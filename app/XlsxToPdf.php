<?php

namespace App;

use Symfony\Component\Process\Process;

class XlsxToPdf
{
    protected $temp_directory;

    /**
     * Constructor
     *
     * @param $temp_directory Directory used for file conversion
     */
    public function __construct($temp_directory = false)
    {
        if (!$temp_directory) {
            $temp_directory = storage_path('app/xlsx_to_pdf');
        }
        /*
         * Create temporary directory if it doesn't exist
         */
        if (!file_exists($temp_directory)) {
            mkdir($temp_directory);
        }

        $this->temp_directory = realpath($temp_directory);
        if ($this->temp_directory === false) {
            throw new \Exception("Temporary directory for XlsxToPdf is not accessible. '{$temp_directory}'", 95930611);
        }
    }

    /**
     * Performs conversion from XLSX to PDF
     *
     * @param $input Contents of XLSX or PDF file
     * @return string|boolen Contents of PDF file or FALSE
     */
    public function __invoke($input)
    {
        try {
            $filename = $this->temp_directory . '/' . uniqid();

            /*
             * If input already contains PDF no need to convert
             * https://en.wikipedia.org/wiki/List_of_file_signatures
             */
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if ($finfo->buffer($input) === 'application/pdf') {
                return $input;
            }

            /*
             * Read input
             */
            $result = file_put_contents($filename . '.xlsx', $input);

            /*
             * Check that input contains a meaningful spreadsheet
             */
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filename . '.xlsx');
            if ($spreadsheet->getActiveSheet()->getHighestRow() < 2) {
                throw new \Exception('Invalid spreadsheet', 65140156);
            };

            /*
             * Perform conversion
             */
            $process = new Process(
                ['libreoffice', '--headless', '--convert-to', 'pdf', $filename . '.xlsx'],
                $this->temp_directory,
                [ 'HOME' => $this->temp_directory ],
            );
            $process->run();

            $errorOutput = $process->getErrorOutput();
            if (!empty($errorOutput)) {
                posix_kill($process->getPid(), SIGTERM);
                throw new \Exception($errorOutput, 22557476);
            }

            /*
             * Process result
             */
            $result = file_get_contents($filename . '.pdf');
            if ($result === false) {
                throw new \Exception('Result file no found', 71402828);
            }

            return $result;
        } finally {
            if (file_exists($filename . '.xlsx')) {
                unlink($filename . '.xlsx');
            }
            if (file_exists($filename . '.pdf')) {
                unlink($filename . '.pdf');
            }
        }
    }
}
