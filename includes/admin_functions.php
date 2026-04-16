<?php
/**
 * Admin-specific functions for dashboard statistics and reporting
 */

function getItemStatistics($pdo) {
    $stats = [];
    
    try {
        // Total items by type
        $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM items GROUP BY type");
        $stmt->execute();
        $typeStats = $stmt->fetchAll();
        foreach ($typeStats as $stat) {
            $stats['items_' . $stat['type']] = $stat['count'];
        }
        
        // Total items by status
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM items GROUP BY status");
        $stmt->execute();
        $statusStats = $stmt->fetchAll();
        foreach ($statusStats as $stat) {
            $stats['status_' . $stat['status']] = $stat['count'];
        }
        
        // Total items
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items");
        $stmt->execute();
        $stats['total_items'] = $stmt->fetch()['count'];
        
        // Recovery rate
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE status IN ('collected', 'verified')");
        $stmt->execute();
        $recovered = $stmt->fetch()['count'];
        $stats['recovered_items'] = $recovered;
        $stats['recovery_rate'] = $stats['total_items'] > 0 ? round(($recovered / $stats['total_items']) * 100, 2) : 0;
        
    } catch (Exception $e) {
        error_log("Error getting item statistics: " . $e->getMessage());
    }
    
    return $stats;
}

function getFrequentlyLostItems($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT category, COUNT(*) as count 
            FROM items 
            WHERE type = 'lost' 
            GROUP BY category 
            ORDER BY count DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting frequently lost items: " . $e->getMessage());
        return [];
    }
}

function getClaimStatistics($pdo) {
    $stats = [];
    
    try {
        // Total claims
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims");
        $stmt->execute();
        $stats['total_claims'] = $stmt->fetch()['count'];
        
        // Claims by status
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM claims GROUP BY status");
        $stmt->execute();
        $claimStats = $stmt->fetchAll();
        foreach ($claimStats as $stat) {
            $stats['claims_' . $stat['status']] = $stat['count'];
        }
        
        // Pending claims
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims WHERE status = 'pending'");
        $stmt->execute();
        $stats['pending_claims'] = $stmt->fetch()['count'];
        
    } catch (Exception $e) {
        error_log("Error getting claim statistics: " . $e->getMessage());
    }
    
    return $stats;
}

function getLinkedItems($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT li.id, 
                   i1.title as lost_title, i1.id as lost_id, i1.status as lost_status,
                   i2.title as found_title, i2.id as found_id, i2.status as found_status,
                   li.match_status, li.created_at,
                   u_lost.name as lost_reporter, u_found.name as found_reporter
            FROM linked_items li
            LEFT JOIN items i1 ON li.lost_item_id = i1.id
            LEFT JOIN items i2 ON li.found_item_id = i2.id
            LEFT JOIN users u_lost ON i1.user_id = u_lost.id
            LEFT JOIN users u_found ON i2.user_id = u_found.id
            ORDER BY li.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting linked items: " . $e->getMessage());
        return [];
    }
}

function getSecurityPersonnel($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE role = 'security' ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting security personnel: " . $e->getMessage());
        return [];
    }
}

function getSystemActivityLog($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                al.id,
                al.user_id,
                al.action_type,
                al.description,
                al.entity_type,
                al.entity_id,
                al.old_value,
                al.new_value,
                al.created_at,
                u.name,
                u.role
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting activity log: " . $e->getMessage());
        return [];
    }
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (count($data) > 0) {
        // Header
        fputcsv($output, array_keys($data[0]));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

function generatePDF($html, $filename) {
    // Note: This requires a PDF library like TCPDF or mPDF
    // For now, we'll provide a basic implementation
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // In production, use TCPDF or similar
    // echo $html;
    // For now, we'll suggest installing TCPDF
    return "PDF generation requires TCPDF library installation.";
}

function exportToPDF($data, $filename, $title = 'Report') {
    // Initialize PDF generator
    $pdf = new SimplePDF();
    
    // Add title page
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(237, 28, 36); // ANU Red
    $pdf->Cell(0, 15, $title, 0, 1, 'C');
    
    // Add metadata
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, 'Generated on: ' . date('F d, Y at H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 8, 'ANU Lost and Found Management System', 0, 1, 'C');
    $pdf->Ln(5);
    
    if (!empty($data)) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(237, 28, 36); // ANU Red
        $pdf->SetTextColor(255, 255, 255);
        
        $headers = array_keys($data[0]);
        $numCols = count($headers);
        $colWidth = 185 / $numCols;
        
        foreach ($headers as $header) {
            $headerText = substr(ucwords(str_replace('_', ' ', $header)), 0, 20);
            $pdf->Cell($colWidth, 7, $headerText, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Table data
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;
        
        foreach ($data as $row) {
            // Check if we need a new page
            if ($pdf->GetY() > 270) {
                $pdf->AddPage();
                // Re-draw header on new page
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(237, 28, 36);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($headers as $header) {
                    $headerText = substr(ucwords(str_replace('_', ' ', $header)), 0, 20);
                    $pdf->Cell($colWidth, 7, $headerText, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $fill = !$fill;
            $bgColor = $fill ? array(240, 240, 240) : array(255, 255, 255);
            $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            
            foreach ($row as $cell) {
                $cellText = is_null($cell) ? 'N/A' : substr(strval($cell), 0, 25);
                $pdf->Cell($colWidth, 6, $cellText, 1, 0, 'L', $fill);
            }
            $pdf->Ln();
        }
    } else {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 10, 'No data available for this report.', 0, 1, 'C');
    }
    
    // Output PDF
    $pdf->Output('D', $filename);
    exit();
}

/**
 * Simple PDF generator class
 * Generates valid PDF documents without external dependencies
 */
class SimplePDF {
    private $pages = array();
    private $currentPage = -1;
    private $x = 10;
    private $y = 10;
    private $pageHeight = 297;
    private $pageWidth = 210;
    private $fontSize = 12;
    private $font = 'Arial';
    private $textColor = array(0, 0, 0);
    private $fillColor = array(255, 255, 255);
    private $objects = array();
    
    public function __construct() {
        $this->AddPage();
    }
    
    public function AddPage() {
        $this->currentPage++;
        $this->pages[$this->currentPage] = '';
        $this->y = 10;
        $this->x = 10;
    }
    
    public function SetFont($family, $style = '', $size = 12) {
        $this->font = $family;
        $this->fontSize = $size;
    }
    
    public function SetTextColor($r, $g = null, $b = null) {
        if ($g === null) {
            $this->textColor = array($r, $r, $r);
        } else {
            $this->textColor = array($r, $g, $b);
        }
    }
    
    public function SetFillColor($r, $g = null, $b = null) {
        if ($g === null) {
            $this->fillColor = array($r, $r, $r);
        } else {
            $this->fillColor = array($r, $g, $b);
        }
    }
    
    public function Cell($w, $h = 6, $txt = '', $border = 0, $ln = 0, $align = 'L', $fill = false) {
        // Add content to current page
        $this->pages[$this->currentPage] .= "q\n";
        
        // Fill background if needed
        if ($fill) {
            $color = $this->fillColor;
            $this->pages[$this->currentPage] .= sprintf(
                "%.3f %.3f %.3f rg %.1f %.1f %.1f %.1f re f\n",
                $color[0]/255, $color[1]/255, $color[2]/255,
                $this->mmToPoints($this->x),
                $this->mmToPoints($this->pageHeight - $this->y - $h),
                $this->mmToPoints($w),
                $this->mmToPoints($h)
            );
        }
        
        // Draw text
        $color = $this->textColor;
        $this->pages[$this->currentPage] .= sprintf(
            "%.3f %.3f %.3f rg\nBT\n/F1 %.1f Tf\n%.1f %.1f Td\n(%.100s) Tj\nET\n",
            $color[0]/255, $color[1]/255, $color[2]/255,
            $this->fontSize,
            $this->mmToPoints($this->x + 1),
            $this->mmToPoints($this->pageHeight - $this->y - $this->fontSize/3),
            str_replace(array('(', ')', '\\'), array('\\(', '\\)', '\\\\'), $txt)
        );
        
        // Draw border if needed
        if ($border) {
            $this->pages[$this->currentPage] .= sprintf(
                "%.2f w %.1f %.1f %.1f %.1f re S\n",
                0.5,
                $this->mmToPoints($this->x),
                $this->mmToPoints($this->pageHeight - $this->y - $h),
                $this->mmToPoints($w),
                $this->mmToPoints($h)
            );
        }
        
        $this->pages[$this->currentPage] .= "Q\n";
        
        // Update position
        if ($ln == 0) {
            $this->x += $w;
        } else {
            $this->x = 10;
            $this->y += $h;
        }
    }
    
    public function Ln($h = 6) {
        $this->x = 10;
        $this->y += $h;
    }
    
    public function GetY() {
        return $this->y;
    }
    
    public function PageNo() {
        return $this->currentPage + 1;
    }
    
    public function Output($dest = 'I', $name = 'document.pdf') {
        // Build PDF content
        $pdf = "%PDF-1.4\n";
        $objects = array();
        $offsets = array();
        
        // Object 1: Catalog
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $pageRefs = '';
        for ($i = 0; $i <= $this->currentPage; $i++) {
            $pageRefs .= (3 + $i) . " 0 R ";
        }
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [" . trim($pageRefs) . "] /Count " . ($this->currentPage + 1) . " >>\nendobj\n";
        
        // Objects 3+: Individual pages
        $contentStartObj = 3 + ($this->currentPage + 1);
        for ($i = 0; $i <= $this->currentPage; $i++) {
            $objNum = 3 + $i;
            $contentObjNum = $contentStartObj + $i;
            $objects[] = $objNum . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents " . $contentObjNum . " 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n";
        }
        
        // Objects: Page content streams
        foreach ($this->pages as $pageContent) {
            $objects[] = (count($objects) + 1) . " 0 obj\n<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream\nendobj\n";
        }
        
        // Build PDF string with offsets
        $pdfContent = $pdf;
        $offset = strlen($pdf);
        
        foreach ($objects as $obj) {
            $offsets[] = $offset;
            $pdfContent .= $obj;
            $offset += strlen($obj);
        }
        
        // Add xref table
        $pdfContent .= "xref\n";
        $pdfContent .= "0 " . (count($objects) + 1) . "\n";
        $pdfContent .= "0000000000 65535 f\n";
        
        foreach ($offsets as $offset) {
            $pdfContent .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n\n";
        }
        
        // Add trailer
        $pdfContent .= "trailer\n";
        $pdfContent .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdfContent .= "startxref\n";
        $pdfContent .= $offset . "\n";
        $pdfContent .= "%%EOF";
        
        // Output
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
    }
    
    private function mmToPoints($mm) {
        return $mm * 2.834645669;
    }
}