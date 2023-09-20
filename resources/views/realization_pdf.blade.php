<!DOCTYPE html>
<html>
<head>
	<title>Realization Advance Employee {{$advance_request["Code"]}}</title>
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
    <h3>Realization Advance Employee #{{$advance_request["Code"]}} </h3>

    </p>

	<table border="1" cellpadding="4">
		<tbody>
            <tr>
                <td width="50%" colspan="2" style="background-color:#CE262A;color:white"><center><strong>Information</strong></center> </td>
                <td width="50%" colspan="2" style="background-color:grey;color:white"><center><strong>Category</strong></center> </td>
            </tr>
            <tr>
                <td>Advance Realization#</td>
                <td>{{$advance_request["Code"]}}</td>

                <td>Company</td>
                <td>{{$advance_request["U_Company"]}}</td>
            </tr>
            <tr>
                <td>Request Date</td>
                <td>{{date('d-M-y', strtotime($advance_request["CreateDate"]))}}</td>

                <td>Pillar</td>
                <td>{{$advance_request["U_Pillar"]}}</td>

            </tr>
            <tr>
                <td>Post Date</td>
                <td>{{date('d-M-y', strtotime($advance_request["U_DisbursedAt"]))}}</td>

                <td>Classification</td>
                <td>{{$advance_request["U_Classification"]}}</td>

            </tr>



            <tr>
                <td>Advance Employee Name</td>
                <td>{{$advance_request["Name"]}}</td>

                <td>SubClass</td>
                <td>{{$advance_request["U_SubClass"]}}</td>

            </tr>
            <tr>
                <td>Requested By</td>
                <td>{{$advance_request["U_RequestorName"]}}</td>

                <td>SubClass2</td>
                <td>{{$advance_request["U_SubClass2"]}}</td>

            </tr>
            <tr>
                <td>Realization Status</td>
                @if($advance_request["U_RealiStatus"] =='1')
                        <td>Unrealized</td>
                @elseif($advance_request["U_RealiStatus"] =='2')
                        <td>Submitted</td>
                @elseif($advance_request["U_RealiStatus"] =='3')
                        <td>Approved by Manager</td>
                @elseif($advance_request["U_RealiStatus"] =='4')
                        <td>Approved by Director</td>
                @elseif($advance_request["U_RealiStatus"] =='5')
                        <td>Rejected</td>
                @elseif($advance_request["U_RealiStatus"] =='6')
                        <td>Confirmed by Finance</td>
                @endif
                <td>Project</td>
                <td>{{$advance_request["U_Project"]}}</td>
            </tr>

            <tr>
                <td>Amount</td>
                <td>Rp {{ number_format( $advance_request["U_Amount"] , 2 , '.' , ',' )}}</td>

                <td>Budget</td>
                <td>{{$advance_request["U_BudgetCode"]}} - {{$advance_request["BudgetName"]}}</td>
            </tr>

            <tr>
                <td>Realization Amount</td>
                <td>Rp {{ number_format( $advance_request["U_RealizationAmt"] , 2 , '.' , ',' )}}</td>


            </tr>
		</tbody>
	</table>

    <p>
    <span margin-top="5px">Advance Items</span>
    </p>



    <table border="1" cellpadding="4" style="width:100%">

        <tbody>
            <tr>
                <td style="background-color:#CE262A;color:white; width:25%"><center><strong>Account</strong></center> </td>
                <td style="background-color:grey;color:white;width:25%" ><center><strong>Item</strong></center> </td>
                <td style="background-color:#CE262A;color:white;width:30%" ><center><strong>Amount</strong></center> </td>
                <td style="background-color:grey;color:white;width:20%"><center><strong>Desc</strong></center> </td>
            </tr>
            @foreach ($advance_request["ADVANCEREQLINESCollection"] as $item)
                <tr>
                    <td>{{$item["U_AccountCode"]}} - {{$item["AccountName"]}}</td>
                    <td>
                    @if($item["ItemName"])
                        {{$item["ItemName"]}}
                    @else
                        {{$item["U_ItemCode"]}}
                    @endif
                    </td>
                    <td>Rp {{ number_format( $item["U_Amount"] , 2 , '.' , ',' )}}</td>
                    <td>{{ $item["U_Description"] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        <span margin-top="5px">Realization Items</span>
        </p>



        <table border="1" cellpadding="4" style="width:100%">

            <tbody>
                <tr>
                    <td style="background-color:#CE262A;color:white; width:25%"><center><strong>Account</strong></center> </td>
                    <td style="background-color:grey;color:white;width:25%" ><center><strong>Item</strong></center> </td>
                    <td style="background-color:#CE262A;color:white;width:30%" ><center><strong>Amount</strong></center> </td>
                    <td style="background-color:grey;color:white;width:20%"><center><strong>Desc</strong></center> </td>
                </tr>
                @foreach ($advance_request["REALIZATIONREQLINESCollection"] as $item)
                    <tr>
                        <td>{{$item["U_AccountCode"]}} - {{$item["AccountName"]}}</td>
                        <td>
                        @if($item["ItemName"])
                            {{$item["ItemName"]}}
                        @else
                            {{$item["U_ItemCode"]}}
                        @endif
                        </td>
                        <td>Rp {{ number_format( $item["U_Amount"] , 2 , '.' , ',' )}}</td>
                        <td>{{ $item["U_Description"] }}</td>
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
