<!DOCTYPE html>
<html>
<head>
	<title>Reimbursement {{$reimbursement["Code"]}}</title>
</head>
<body>
	<style type="text/css">
		table tr td,
		table tr th{
			font-size: 9pt;
		}
        body {
            font-family: 'Arial, Helvetica, sans-serif'
        }
        h4 {
            font-family: 'Arial, Helvetica, sans-serif'
        }

        #signature {
            width: 100%;
            border-bottom: 1px solid black;
            height: 30px;
        }
	</style>


    <p>
    <h3> Reimbursement #{{$reimbursement["Code"]}} </h3>

    </p>

	<table border="1" cellpadding="4">
		<tbody>
            <tr>
                <td width="50%" colspan="2" style="background-color:#CE262A;color:white"><center><strong>Information</strong></center> </td>
                <td width="50%" colspan="2" style="background-color:grey;color:white"><center><strong>Category</strong></center> </td>
            </tr>
            <tr>
                <td>Reimbursement#</td>
                <td>{{$reimbursement["Code"]}}</td>

                <td>Company</td>
                <td>{{$reimbursement["U_Company"]}}</td>
            </tr>
            <tr>
                <td>Request Date</td>
                <td>{{date('d-M-y', strtotime($reimbursement["CreateDate"]))}}</td>

                <td>Pillar</td>
                <td>{{$reimbursement["U_Pillar"]}}</td>

            </tr>
            <tr>
                <td>Post Date</td>
                @if($reimbursement["U_RequestDate"])
                <td>{{date('d-M-y', strtotime($reimbursement["U_RequestDate"]))}}</td>
                @else
                <td>-</td>
                @endif

                <td>Classification</td>
                <td>{{$reimbursement["U_Classification"]}}</td>

            </tr>



            <tr>
                <td>Reimbursement Name</td>
                <td>{{$reimbursement["Name"]}}</td>

                <td>SubClass</td>
                <td>{{$reimbursement["U_SubClass"]}}</td>

            </tr>
            <tr>
                <td>Requested By</td>
                <td>{{$reimbursement["U_RequestorName"]}}</td>

                <td>SubClass2</td>
                <td>{{$reimbursement["U_SubClass2"]}}</td>

            </tr>
            <tr>
                <td>Status</td>
                @if($reimbursement["U_Status"] =='1')
                        <td>Pending</td>
                @elseif($reimbursement["U_Status"] =='2')
                        <td>Approved by Manager</td>
                @elseif($reimbursement["U_Status"] =='3')
                        <td>Approved by Director</td>
                @elseif($reimbursement["U_Status"] =='4')
                        <td>Rejected</td>
                @elseif($reimbursement["U_Status"] =='5')
                        <td>Transferred</td>
                @endif
                <td>Project</td>
                <td>{{$reimbursement["U_Project"]}}</td>
            </tr>

            <tr>
                <td></td>
                <td></td>

                <td>Budget</td>
                <td>{{$reimbursement["U_BudgetCode"]}} - {{$reimbursement["BudgetName"]}}</td>
            </tr>
		</tbody>
	</table>

    <p>
    <span margin-top="5px">ACCOUNTS</span>
    </p>



    <table border="1" cellpadding="4" style="width:100%">

        <tbody>
            <tr>
                <td style="background-color:#CE262A;color:white; width:50%"><center><strong>Account</strong></center> </td>
                <td style="background-color:grey;color:white;width:15%" ><center><strong>Amount</strong></center> </td>
                <td style="background-color:#CE262A;color:white;width:10%"><center><strong>PPH</strong></center> </td>
                <td style="background-color:grey;color:white;width:25%"><center><strong>Desc</strong></center> </td>
            </tr>
            @foreach ($reimbursement["REIMBURSEMENTLINESCollection"] as $account)
                <tr>
                    <td>{{$account["U_AccountCode"]}} - {{$account["AccountName"]}}</td>
                    <td>Rp {{ number_format( $account["U_Amount"] , 2 , '.' , ',' )}}</td>
                    @if($account["U_NPWP"] == '')
                    <td>0 %</td>
                    @else
                    <td>{{ $account["U_NPWP"] }} %</td>
                    @endif
                    <td>{{ $account["U_Description"] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 20px;"></div>
    <table width="100%" border="1" cellpadding="4">
        <tr>
            <td style="height:70px;width:40%" colspan="2">Notes :</td>
            <td style="width:30%">Prepared by,</td>
            <td style="width:30%">Approved by,</td>
        </tr>
    </table>






</body>

</html>
