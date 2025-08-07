<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class ExportService
{
    /**
     * Export data to CSV format
     */
    public function exportToCsv(Collection $data, array $headers, string $filename = 'export.csv'): HttpResponse
    {
        $csvData = $this->convertToCsvFormat($data, $headers);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return Response::make($csvData, 200, $headers);
    }

    /**
     * Export data to Excel format (CSV with Excel-friendly formatting)
     */
    public function exportToExcel(Collection $data, array $headers, string $filename = 'export.xlsx'): HttpResponse
    {
        $csvData = $this->convertToCsvFormat($data, $headers, true);
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return Response::make($csvData, 200, $headers);
    }

    /**
     * Convert collection data to CSV format
     */
    protected function convertToCsvFormat(Collection $data, array $headers, bool $excelFormat = false): string
    {
        $output = '';
        
        // Add BOM for Excel compatibility if needed
        if ($excelFormat) {
            $output .= "\xEF\xBB\xBF";
        }
        
        // Add headers
        $output .= $this->arrayToCsvLine(array_values($headers));
        
        // Add data rows
        foreach ($data as $row) {
            $rowData = [];
            foreach (array_keys($headers) as $key) {
                $value = $this->getNestedValue($row, $key);
                $rowData[] = $this->formatCsvValue($value);
            }
            $output .= $this->arrayToCsvLine($rowData);
        }
        
        return $output;
    }

    /**
     * Convert array to CSV line
     */
    protected function arrayToCsvLine(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $data);
        rewind($output);
        $line = fgets($output);
        fclose($output);
        
        return $line;
    }

    /**
     * Get nested value from object/array using dot notation
     */
    protected function getNestedValue($item, string $key)
    {
        if (strpos($key, '.') === false) {
            return is_object($item) ? ($item->$key ?? '') : ($item[$key] ?? '');
        }
        
        $keys = explode('.', $key);
        $value = $item;
        
        foreach ($keys as $nestedKey) {
            if (is_object($value)) {
                $value = $value->$nestedKey ?? null;
            } elseif (is_array($value)) {
                $value = $value[$nestedKey] ?? null;
            } else {
                return '';
            }
            
            if ($value === null) {
                return '';
            }
        }
        
        return $value;
    }

    /**
     * Format value for CSV output
     */
    protected function formatCsvValue($value): string
    {
        if ($value === null) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        if (is_object($value) && method_exists($value, 'format')) {
            // Handle Carbon dates
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        return (string) $value;
    }

    /**
     * Export users to CSV
     */
    public function exportUsers(Collection $users, string $filename = 'users_export.csv'): HttpResponse
    {
        $headers = [
            'id' => 'ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of Birth',
            'country.name' => 'Country',
            'city.name' => 'City',
            'current_address' => 'Address',
            'marital_status' => 'Marital Status',
            'status' => 'Status',
            'email_verified_at' => 'Email Verified',
            'created' => 'Registration Date'
        ];
        
        return $this->exportToCsv($users, $headers, $filename);
    }

    /**
     * Export organizations to CSV
     */
    public function exportOrganizations(Collection $organizations, string $filename = 'organizations_export.csv'): HttpResponse
    {
        $headers = [
            'id' => 'ID',
            'name' => 'Organization Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'website' => 'Website',
            'about' => 'About',
            'address' => 'Address',
            'country.name' => 'Country',
            'city.name' => 'City',
            'category.name' => 'Category',
            'status' => 'Status',
            'is_verified' => 'Verified',
            'created' => 'Registration Date'
        ];
        
        return $this->exportToCsv($organizations, $headers, $filename);
    }

    /**
     * Export events to CSV
     */
    public function exportEvents(Collection $events, string $filename = 'events_export.csv'): HttpResponse
    {
        $headers = [
            'id' => 'ID',
            'title' => 'Event Title',
            'description' => 'Description',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'location' => 'Location',
            'country.name' => 'Country',
            'city.name' => 'City',
            'organization.name' => 'Organization',
            'requesting_volunteers' => 'Requesting Volunteers',
            'status' => 'Status',
            'created' => 'Created Date'
        ];
        
        return $this->exportToCsv($events, $headers, $filename);
    }

    /**
     * Export news to CSV
     */
    public function exportNews(Collection $news, string $filename = 'news_export.csv'): HttpResponse
    {
        $headers = [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'organization.name' => 'Organization',
            'country.name' => 'Country',
            'status' => 'Status',
            'published_at' => 'Published Date',
            'created' => 'Created Date'
        ];
        
        return $this->exportToCsv($news, $headers, $filename);
    }

    /**
     * Export resources to CSV
     */
    public function exportResources(Collection $resources, string $filename = 'resources_export.csv'): HttpResponse
    {
        $headers = [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'organization.name' => 'Organization',
            'resourceType.name' => 'Resource Type',
            'file_type' => 'File Type',
            'status' => 'Status',
            'created' => 'Created Date'
        ];
        
        return $this->exportToCsv($resources, $headers, $filename);
    }

    /**
     * Generate export statistics
     */
    public function getExportStats(string $type, array $filters = []): array
    {
        // This would typically query the database for export statistics
        // For now, return placeholder data
        return [
            'type' => $type,
            'total_records' => 0,
            'filtered_records' => 0,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'filters_applied' => $filters
        ];
    }
}