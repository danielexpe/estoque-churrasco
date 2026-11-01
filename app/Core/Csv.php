<?php

declare(strict_types=1);

namespace App\Core;

final class Csv
{
    public static function download(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
