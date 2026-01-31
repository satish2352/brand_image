<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CampaignRepository;
use Illuminate\Support\Facades\DB;
use App\Mail\AdminCampaignCreatedMail;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CampaignExport;
use App\Http\Controllers\Website\CampaignController;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Maatwebsite\Excel\Excel as ExcelExcel;

class CampaignService
{
    public function __construct(
        protected CampaignRepository $repo
    ) {}

    public function saveCampaign($userId, $campaignName)
    {
        $this->repo->createCampaignAndMoveCart($userId, $campaignName);
        return true;
    }

    public function getCampaignList($userId, $request)
    {
        $data_output = $this->repo->getCampaignList($userId, $request);

        return $data_output;
    }
    public function getOpenCampaigns($userId, $request)
    {
        $data_output = $this->repo->getOpenCampaigns($userId, $request);

        return $data_output;
    }

    public function getBookedCampaigns($userId, $request)
    {
        return $this->repo->fetchBookedCampaigns($userId, $request);
    }

    public function getPastCampaigns($userId, $request)
    {
        return $this->repo->fetchPastCampaigns($userId, $request);
    }

    public function getCampaignDetailsByCartItem($userId, $cartItemId)
    {
        $data_output = $this->repo->getCampaignDetailsByCartItem($userId, $cartItemId);
        // dd($data_output);
        // die();
        return $data_output;
    }

    // public function sendCampaignMailToAdmin($userId): void
    // {
    //     $campaign = DB::table('campaign')
    //         ->where('user_id', $userId)
    //         ->latest()
    //         ->first();

    //     if (!$campaign) {
    //         throw new \Exception('Campaign not found');
    //     }

    //     /* ---------- TEMP DIR ---------- */
    //     $tempDir = storage_path('app/temp');

    //     if (!is_dir($tempDir)) {
    //         mkdir($tempDir, 0777, true);
    //     }

    //     $excelPath = $tempDir . "/campaign_{$campaign->id}.xlsx";
    //     $pptPath   = $tempDir . "/campaign_{$campaign->id}.pptx";

    //     @unlink($excelPath);
    //     @unlink($pptPath);

    //     /* ---------- EXCEL (SAFE WAY) ---------- */
    //     try {
    //         $response = Excel::download(
    //             new CampaignExport($userId, $campaign->id),
    //             'campaign.xlsx'
    //         );

    //         file_put_contents(
    //             $excelPath,
    //             $response->getFile()->getContent()
    //         );
    //     } catch (\Throwable $e) {
    //         throw new \Exception('Excel generation failed: ' . $e->getMessage());
    //     }

    //     if (!file_exists($excelPath)) {
    //         throw new \Exception('Excel file not generated');
    //     }

    //     /* ---------- PPT ---------- */
    //     app(\App\Http\Controllers\Website\CampaignController::class)
    //         ->generatePptFile($campaign->id, $pptPath);

    //     if (!file_exists($pptPath)) {
    //         throw new \Exception('PPT file not generated');
    //     }

    //     /* ---------- SEND MAIL ---------- */
    //     Mail::to(config('mail.mailers.smtp.admin_email'))
    //         ->send(new AdminCampaignCreatedMail(
    //             $campaign,
    //             $excelPath,
    //             $pptPath
    //         ));
    // }

    public function sendCampaignMailToAdmin($userId): void
    {
        $campaign = DB::table('campaign')
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if (!$campaign) {
            throw new \Exception('Campaign not found');
        }

        // Excel in memory
        $excelBinary = Excel::raw(
            new CampaignExport($userId, $campaign->id),
            ExcelExcel::XLSX
        );

        // PPT in memory
        $pptBinary = app(CampaignController::class)
            ->generatePptBinary($campaign->id);

        // Send mail
        Mail::to(config('mail.mailers.smtp.admin_email'))
            ->send(new AdminCampaignCreatedMail(
                $campaign,
                $excelBinary,
                $pptBinary
            ));
    }

}
