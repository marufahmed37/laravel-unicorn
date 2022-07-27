@extends('layouts.dashboard')
@section('content')
    <div class="row">
        <div class="col-lg-10 m-auto">
            <div class="card">
                <div class="card-header">

                    <h4 class="font-weight-bold text-black">User List <span class="float-end">Total User:
                            {{ $total_user }} </span></h4>
                    {{-- <h3 class="float-end">Count: <span></span></h3> --}}

                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Create at</th>
                                <th>Action</th>

                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($all_users as $key => $user)
                                <tr class="{{ $loop->odd ? 'text-danger' : 'text-success' }}">
                                    <td>{{ $all_users->firstitem() + $key }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                    <td>
                                        <button name=" {{ route('user.delete', $user->id) }}" type="submit"
                                            class="delete_btn btn btn-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                    {{ $all_users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer_script')
    <script>
        $(document).ready(function() {


            $('.delete_btn').click(function() {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // window.location.href=link,
                        // Swal.fire(
                        //   'Deleted!',
                        //   'Your file has been deleted.',
                        //   'success'

                        // )
                        var link = $(this).attr('name')
                        // alert(link)
                        window.location.href = link




                    }


                })

            });
        });
    </script>
    @if (session('delete'))
        <script>
            Swal.fire(
                'Deleted!',
                '{{ session('delete') }}',
                'success'

            )
        </script>
    @endif
@endsection
