<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\XlsxToPdf;

class XlsxToPdfTest extends TestCase
{
    private $temp_directory;

    public function test_xlsx_to_pdf()
    {
        // If PDF is provided as input it is returned as is
        $result = app(XlsxToPdf::class)(file_get_contents('tests/Feature/XlsxToPdf.pdf'));
        $this->assertIsString($result);

        // Plain XLSX returns PDF
        $result = app(XlsxToPdf::class)(file_get_contents('tests/Feature/XlsxToPdf.xlsx'));
        $this->assertIsString($result);
    }

    public function test_temp_directory()
    {
        // Exception is thrown if temporary directory doesn't exist
        $this->expectException(\Exception::class);
        $xlsxToPdf = new XlsxToPdf(storage_path('not/a/directory'));
    }

    public function test_invalid_spreadsheet()
    {
        // Exception is thrown on incorrect spreadsheet
        $this->expectException(\Exception::class);
        app(XlsxToPdf::class)('dummy text');
    }
}
