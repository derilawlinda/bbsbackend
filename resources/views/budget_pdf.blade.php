<!DOCTYPE html>
<html>
<head>
	<title>Budget {{$budget["Code"]}}</title>
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
	</style>

    <p>
        <h3> Budget Request #{{$budget["Code"]}} </h3>

    </p>


    <table border="1" cellpadding="4">
		<tbody>
            <tr>
                <td width="50%" colspan="2" style="background-color:#CE262A;color:white"><center><strong>Information</strong></center> </td>
                <td width="50%" colspan="2" style="background-color:grey;color:white"><center><strong>Category</strong></center> </td>
            </tr>
            <tr>
                <td>Budget#</td>
                <td>{{$budget["Code"]}}</td>

                <td>Company</td>
                <td>{{$budget["U_Company"]}}</td>

            </tr>
            <tr>
                <td>Request Date</td>
                <td>{{date('d-M-y', strtotime($budget["CreateDate"]))}}</td>
                <td>Pillar</td>
                <td>{{$budget["U_Pillar"]}}</td>

            </tr>
            <tr>
                <td>Budget Name</td>
                <td>{{$budget["Name"]}}</td>
                <td>Classification</td>
                <td>{{$budget["U_Classification"]}}</td>

            </tr>
            <tr>
                <td>Requested By</td>
                <td>{{$budget["U_RequestorName"]}}</td>

                <td>SubClass</td>
                <td>{{$budget["U_SubClass"]}}</td>

            </tr>
            <tr>
                <td>Status</td>
                @if($budget["U_Status"] =='1')
                        <td>Pending</td>
                @elseif($budget["U_Status"] =='2')
                        <td>Approved by Manager</td>
                @elseif($budget["U_Status"] =='3')
                        <td>Approved by Director</td>
                @endif

                <td>SubClass2</td>
                <td>{{$budget["U_SubClass2"]}}</td>

            </tr>

            <tr>
                <td>Amount</td>
                <td>Rp {{ number_format( $budget["U_TotalAmount"] , 2 , '.' , ',' )}}</td>

                <td>Project</td>
                <td>{{$budget["U_Project"]}}</td>
            </tr>
		</tbody>
	</table>

    <p>
        <span margin-top="5px">Accounts</span>
    </p>

    <table border="1" cellpadding="4">

        <tbody>
            <tr>
                <td width="50%" style="background-color:#CE262A;color:white"><center><strong>Account</strong></center> </td>
                <td width="50%" style="background-color:grey;color:white"><center><strong>Amount</strong></center> </td>
            </tr>
            @foreach ($budget["BUDGETREQLINESCollection"] as $account)
                <tr>
                    <td>{{$account["U_AccountCode"]}} - {{$account["AccountName"]}}</td>
                    <td>Rp {{ number_format( $account["U_Amount"] , 2 , '.' , ',' )}}</td>
                </tr>
            @endforeach
            <tr>
                <td style="background-color:gainsboro">Total </td>
                <td > <strong> Rp {{ number_format( $budget["U_TotalAmount"] , 2 , '.' , ',' )}} </strong></td>
            </tr>
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
