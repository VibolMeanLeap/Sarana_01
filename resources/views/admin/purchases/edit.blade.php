<form action="{{route('purchase.update',$purchase->id)}}" method="POST">

@csrf

@method('PUT')