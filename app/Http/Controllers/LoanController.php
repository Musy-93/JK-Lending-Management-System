<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class LoanController extends Controller
{
    // Auth Middleware restricts access to this controller to only authorized users
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Deletes the given loan
    public function deleteLoan($loan_id)
    {
        $deleteLoan = DB::table('loans')
                        ->where('id', $loan_id)
                        ->delete();

        return Response::json($deleteLoan);
    }

    public function getLoanDetails($loan_id)
    {
    	// Gets all the latest ledgers with their associated column data from other tables
    	$query = DB::table('loans')
    				->leftJoin('borrowers', 'loans.borrower_id', '=', 'borrowers.id')
    				->leftJoin('companies', 'borrowers.company_id', '=', 'companies.id')
                    ->leftJoin('cash_advance_status', 'loans.cash_advance_status_id', '=', 'cash_advance_status.id')
                    ->leftJoin('term_type', 'loans.term_type_id', '=', 'term_type.id')
                    ->leftJoin('loan_status', 'loans.loan_status_id', '=', 'loan_status.id')
    				->leftJoin('remittance_dates', 'loans.remittance_date_id', '=', 'remittance_dates.id')
    				->select('loans.*', 'borrowers.name as borrower_name', 'companies.name as company_name', 'cash_advance_status.name as cash_advance_status', 'term_type.name as term_type', 'loan_status.name as loan_status', 'remittance_dates.remittance_date as remittance_date')
    				->where('loans.id', $loan_id)
                    ->get();

    	return $query;
    }

    public function readLateTotalRemittance($loan)
    {
        $getLate = DB::table('late_remittance_amount')
                   ->select('amount')
                   ->where('loan_id', $loan)
                   ->get();

        if($getLate == null)
        {
            return 0.00;
        }
        else
            return $getLate;
    }

    public function readTotalRemittance($loan)
    {
        return DB::table('loan_remittances')
               ->selectRaw('SUM(loan_remittances.amount) as sum')
               ->where('loan_remittances.loan_id', $loan)
               ->get();
    }

    public function show($loan)
    {
        // If the loan is fully paid, change the loan status to fully paid
        $totalRemittances = $this->readTotalRemittance($loan);

        $loanBalance = DB::table('loans')
                        ->select('loans.interested_amount', 'loans.loan_status_id')
                        ->where('loans.id', $loan)
                        ->first();  

        // if($totalRemittances->sum >= $loanBalance->interested_amount)
        //     $this->updateLoanStatus(2, $loan);
        // else
        // {
        //     // Only change the loan status to 'Not Fully Paid' if it was not 'Late'
        //     if($loanBalance->loan_status_id != "3")
        //         $this->updateLoanStatus(1, $loan);
        // }

        $details = $this->getLoanDetails($loan);

        return view('loans.record')->with('details', $details)->with('totalRemittances', $totalRemittances)->with('loanBalance', $loanBalance);
    }

    // Updates the due date of a loan
    public function updateDueDate($loan, $date)
    {
        $updateDueDate = DB::table('loans')
                        ->where('id', $loan)
                        ->update([
                            'due_date' => $date
                        ]);
    }

    // Updates the Loan Status in the Loan Table in the database
    public function updateLoanStatus($status, $id)
    {
        $updateLoanStatus = DB::table('loans')
                            ->where('loans.id', $id)
                            ->update(['loan_status_id' => $status]);
    }
}
