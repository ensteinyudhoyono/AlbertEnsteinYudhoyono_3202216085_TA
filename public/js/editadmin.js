$(document).ready(function () {
    $(".editadmin").on("click", function () {
        const id = $(this).data("id");
        $.ajax({
            url: "/dashboard/admin/" + id + "/edit",
            data: {
                id: id,
            },
            type: "get",
            dataType: "json",
            success: function (data) {
                $("#id").val(data.id);
                $("#name").val(data.name);
                $("#nomor_induk").val(data.nomor_induk);
                $("#email").val(data.email);
                $("#password").val(data.password);
                $("#role_id").val(data.role_id);
                // Only set form action if we're in the admin context
                if ($("#edituser").hasClass("admin-edit")) {
                    $("#editformuser").attr(
                        "action",
                        "/dashboard/users/" + data.id
                    );
                    console.log("Admin edit: Form action set to:", "/dashboard/users/" + data.id);
                }
            },
        });
    });
});
