$(".plan-delete").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to delete this Item!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$(".distpoint-delete").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to delete this Item!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$(".invoice-delete").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to delete this Item!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$(".item-restore").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to Restore this Customer!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, Restore it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$(".invoice-cancel").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want cancel this invoice!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();setTimeout(function() {}, 10);
		}
	})
});

$(".invoice-bulk").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You Want Create Multiple Mounthly Invoice ?!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes!'
	}).then((result) => {
		if (result.value) {
			location.href = '/invoice/bulk';
		}
	})
});


$(".invoice-verify").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'You want Verify this transaction?',
		text: "",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});


//Site

$(".invoice-create-confirm").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to create this Invoice!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, create it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});


$(".site-delete").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to delete this Item!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});


$(".notifinv-send").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to Sent Notification to Customers!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, Send it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$(".notifblocked-send").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to Sent Notification to Customers!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, Send it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});
$(".createmonthlyinv-send").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want Create Monthly Invoice to Customers!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, Send it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});


// function getSelectedisolirdate() {
// 	// var isolirdate = $('#isolir_date').val();
// 	// $.ajax({
// 	// 	url: '/jobs/isolirdata',
// 	// 	type: 'GET',
// 	// 	data: { isolirdate: isolirdate },
// 	// 	success: function(response) {
// 	// 		$('#result').html(response.message);
// 	// 	}
// 	// });
// 	alert("hhhh");
// }

//Site
// $(".pushbutton").on("click", function(e) {
// 	 var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

// 	 $.ajax({
//                     /* the route pointing to the post function */
//                     url: '/postajax',
//                     type: 'POST',
//                     /* send the csrf-token and the input to the controller */
//                     data: {_token: CSRF_TOKEN, message:$(".getinfo").val()},
//                     dataType: 'JSON',
//                     /* remind that 'data' is the response of the AjaxController */
//                     success: function (data) { 
//                        // $(".writeinfo").append(data.msg); 
//                          alert(data.success);
//                     }
//                 }); 
// });


$(".item-delete").on("click", function(e) {
	e.preventDefault();
	Swal.fire({
		title: 'Are you sure?',
		text: "You want to delete this Item!",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.value) {
			$(this).closest('form').submit();
		}
	})
});

$("#reboot").on("submit", function(e) {
    e.preventDefault(); // Mencegah submit otomatis
    Swal.fire({
    	title: 'Are you sure?',
    	text: "You want to submit this form!",
    	icon: 'warning',
    	showCancelButton: true,
    	confirmButtonColor: '#3085d6',
    	cancelButtonColor: '#d33',
    	confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
    	if (result.isConfirmed) {
            // Jika pengguna mengkonfirmasi, submit form
    		e.target.submit();
    	}
    });
});


