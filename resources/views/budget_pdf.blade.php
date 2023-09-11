<!DOCTYPE html>
<html>
<head>
	<title>Budget {{$budget["Code"]}}</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
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
                <td>Pillar</td>
                <td>{{$budget["U_Pillar"]}}</td>
            </tr>
            <tr>
                <td>Request Date</td>
                <td>{{date('d-M-y', strtotime($budget["CreateDate"]))}}</td>
                <td>Classification</td>
                <td>{{$budget["U_Classification"]}}</td>
            </tr>
            <tr>
                <td>Budget Name</td>
                <td>{{$budget["Name"]}}</td>
                <td>SubClass</td>
                <td>{{$budget["U_SubClass"]}}</td>
            </tr>
            <tr>
                <td>Requested By</td>
                <td>{{$budget["U_RequestorName"]}}</td>

                <td>SubClass2</td>
                <td>{{$budget["U_SubClass2"]}}</td>
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
        </tbody>
    </table>


</body>
</html>
