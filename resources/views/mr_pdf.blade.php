<!DOCTYPE html>
<html>
<head>
	<title>Material Request {{$material_request["Code"]}}</title>
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
    <h3> Material Request #{{$material_request["Code"]}} </h3>

    </p>

	<table border="1" cellpadding="4">
		<tbody>
            <tr>
                <td width="50%" colspan="2" style="background-color:#CE262A;color:white"><center><strong>Information</strong></center> </td>
                <td width="50%" colspan="2" style="background-color:grey;color:white"><center><strong>Category</strong></center> </td>
            </tr>
            <tr>
                <td>MR#</td>
                <td>{{$material_request["Code"]}}</td>

                <td>Company</td>
                <td>{{$material_request["U_Company"]}}</td>
            </tr>
            <tr>
                <td>Request Date</td>
                <td>{{date('d-M-y', strtotime($material_request["CreateDate"]))}}</td>

                <td>Pillar</td>
                <td>{{$material_request["U_Pillar"]}}</td>

            </tr>
            <tr>
                <td>Post Date</td>
                <td>{{date('d-M-y', strtotime($material_request["U_DocDate"]))}}</td>

                <td>Classification</td>
                <td>{{$material_request["U_Classification"]}}</td>

            </tr>



            <tr>
                <td>Material Request Name</td>
                <td>{{$material_request["U_SubClass"]}}</td>

                <td>SubClass</td>
                <td>{{$material_request["U_SubClass"]}}</td>

            </tr>
            <tr>
                <td>Requested By</td>
                <td>{{$material_request["U_RequestorName"]}}</td>

                <td>SubClass2</td>
                <td>{{$material_request["U_SubClass2"]}}</td>

            </tr>
            <tr>
                <td>Status</td>
                @if($material_request["U_Status"] =='1')
                        <td>Pending</td>
                @elseif($material_request["U_Status"] =='2')
                        <td>Approved by Manager</td>
                @elseif($material_request["U_Status"] =='3')
                        <td>Approved by Director</td>
                @endif
                <td>Project</td>
                <td>{{$material_request["U_Project"]}}</td>
            </tr>

            <tr>
                <td></td>
                <td></td>

                <td>Budget</td>
                <td>{{$material_request["U_BudgetCode"]}} - {{$material_request["BudgetName"]}}</td>
            </tr>
		</tbody>
	</table>

    <p>
    <span margin-top="5px">Items</span>
    </p>

    <table border="1" cellpadding="4">

        <tbody>
            <tr>
                <td style="background-color:#CE262A;color:white"><center><strong>Account</strong></center> </td>
                <td style="background-color:grey;color:white"><center><strong>Item</strong></center> </td>
                <td style="background-color:#CE262A;color:white"><center><strong>Qty</strong></center> </td>
            </tr>
            @foreach ($material_request["MATERIALREQLINESCollection"] as $item)
                <tr>
                    <td>{{$item["U_AccountCode"]}} - {{$item["AccountName"]}}</td>
                    <td>{{$item["U_ItemCode"]}} - {{$item["ItemName"]}}</td>
                    <td>{{ $item["U_Qty"] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>


</body>
</html>
