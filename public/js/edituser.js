$(document).ready(function () {
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Add test button to page for debugging
    if ($("#edituser").length > 0) {
        const testButton = $('<button type="button" class="btn btn-info btn-sm ms-2" onclick="testEditFormManually()">Test Form</button>');
        const ajaxTestButton = $('<button type="button" class="btn btn-warning btn-sm ms-2" onclick="testAjaxCall(1)">Test AJAX</button>');
        $("#edituser .modal-header").append(testButton);
        $("#edituser .modal-header").append(ajaxTestButton);
    }
    
    // Test function to verify form population
    window.testEditForm = function() {
        console.log("Testing edit form population...");
        console.log("Modal element exists:", $("#edituser").length > 0);
        console.log("Form element exists:", $("#editformuser").length > 0);
        console.log("Form fields exist:");
        console.log("- ID field:", $("#id").length > 0);
        console.log("- Name field:", $("#name").length > 0);
        console.log("- Email field:", $("#email").length > 0);
        console.log("- Organization field:", $("#organization").length > 0);
        console.log("- Phone field:", $("#phone").length > 0);
        console.log("- Address field:", $("#address").length > 0);
        console.log("- Role field:", $("#role_id").length > 0);
        console.log("- Status field:", $("#status").length > 0);
    };
    
    // Manual test function
    window.testEditFormManually = function() {
        console.log("=== MANUAL TEST OF EDIT FORM ===");
        
        // Test 1: Check if modal is visible
        console.log("1. Modal visible:", $("#edituser").hasClass('show'));
        
        // Test 2: Check if form exists
        console.log("2. Form exists:", $("#editformuser").length > 0);
        
        // Test 3: Check all form fields
        const fields = ['id', 'name', 'email', 'organization', 'phone', 'address', 'role_id', 'status'];
        fields.forEach(field => {
            const element = $("#" + field);
            console.log(`3. Field ${field}:`, element.length > 0, "Value:", element.val());
        });
        
        // Test 4: Check form action
        console.log("4. Form action:", $("#editformuser").attr("action"));
        
        // Test 5: Check form method
        console.log("5. Form method:", $("#editformuser").attr("method"));
        
        // Test 6: Check _method field
        const methodField = $("#editformuser").find('input[name="_method"]');
        console.log("6. _method field:", methodField.length > 0, "Value:", methodField.val());
        
        // Test 7: Check CSRF token
        const csrfField = $("#editformuser").find('input[name="_token"]');
        console.log("7. CSRF token:", csrfField.length > 0, "Value:", csrfField.val());
        
        console.log("=== END MANUAL TEST ===");
    };
    
    // Test function to simulate edit user click
    window.testEditUserClick = function(userId = 1) {
        console.log("=== TESTING EDIT USER CLICK ===");
        console.log("Simulating edit user click for ID:", userId);
        
        // Create a mock edit button
        const mockButton = $('<a href="#" class="edituser" data-id="' + userId + '">Test Edit</a>');
        
        // Trigger the click event
        mockButton.trigger('click');
        
        console.log("=== EDIT USER CLICK TEST COMPLETE ===");
    };
    
    // Test function to populate form with mock data
    window.testPopulateForm = function() {
        console.log("=== TESTING FORM POPULATION ===");
        
        const mockData = {
            id: 999,
            name: "Test User",
            email: "test@example.com",
            organization: "Test Organization",
            phone: "081234567890",
            address: "Test Address",
            role_id: 1,
            status: "active"
        };
        
        console.log("Mock data:", mockData);
        
        // Populate form fields
        $("#id").val(mockData.id);
        $("#name").val(mockData.name);
        $("#email").val(mockData.email);
        $("#organization").val(mockData.organization);
        $("#phone").val(mockData.phone);
        $("#address").val(mockData.address);
        $("#role_id").val(mockData.role_id);
        $("#status").val(mockData.status);
        
        console.log("Form populated with mock data");
        
        // Verify population
        console.log("Verification:");
        console.log("ID:", $("#id").val());
        console.log("Name:", $("#name").val());
        console.log("Email:", $("#email").val());
        console.log("Organization:", $("#organization").val());
        console.log("Phone:", $("#phone").val());
        console.log("Address:", $("#address").val());
        console.log("Role:", $("#role_id").val());
        console.log("Status:", $("#status").val());
        
        console.log("=== FORM POPULATION TEST COMPLETE ===");
    };
    
    // Test function to manually test AJAX call for a specific user
    window.testAjaxCall = function(userId = 1) {
        console.log("=== TESTING AJAX CALL MANUALLY ===");
        console.log("Testing AJAX call for user ID:", userId);
        
        // Clear form first
        $("#editformuser")[0].reset();
        
        // Set form action
        const formAction = "/dashboard/users/" + userId;
        $("#editformuser").attr("action", formAction);
        $("#id").val(userId);
        
        console.log("Form prepared for user ID:", userId);
        
        // Make the AJAX call
        $.ajax({
            url: "/dashboard/users/" + userId + "/edit",
            data: { id: userId },
            type: "get",
            dataType: "json",
            success: function(data) {
                console.log("=== AJAX SUCCESS ===");
                console.log("Received data:", data);
                console.log("User ID:", data.id);
                console.log("User name:", data.name);
                console.log("User email:", data.email);
                
                // Populate form
                $("#name").val(data.name || '');
                $("#email").val(data.email || '');
                $("#organization").val(data.organization || '');
                $("#phone").val(data.phone || '');
                $("#address").val(data.address || '');
                $("#role_id").val(data.role_id || '');
                $("#status").val(data.status || 'active');
                
                console.log("Form populated with received data");
                console.log("=== AJAX TEST COMPLETE ===");
            },
            error: function(xhr, status, error) {
                console.error("=== AJAX ERROR ===");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response:", xhr.responseText);
                console.log("=== AJAX TEST FAILED ===");
            }
        });
    };
    
    $(".edituser").on("click", function (e) {
        e.preventDefault();
        const id = $(this).data("id");
        
        // Debug: Log which user is being edited
        console.log("Edit user clicked for ID:", id);
        console.log("User name from link:", $(this).closest('tr').find('td:eq(1)').text().trim());
        console.log("User email from link:", $(this).closest('tr').find('td:eq(2)').text().trim());
        
        // Test form elements before AJAX
        console.log("Testing form elements before AJAX...");
        window.testEditForm();
        
        // Set form action immediately when button is clicked
        const formAction = "/dashboard/users/" + id;
        $("#editformuser").attr("action", formAction);
        console.log("Form action set immediately to:", formAction);
        
        // Also set the hidden ID field immediately
        $("#id").val(id);
        console.log("Hidden ID field set to:", id);
        
        // Clear previous values and errors
        $("#editformuser")[0].reset();
        $(".invalid-feedback").hide();
        $(".is-invalid").removeClass("is-invalid");
        
        // Debug: Log the exact URL being requested
        const ajaxUrl = "/dashboard/users/" + id + "/edit";
        console.log("=== AJAX REQUEST DETAILS ===");
        console.log("Requesting URL:", ajaxUrl);
        console.log("User ID from button:", id);
        console.log("Current user row data:", {
            name: $(this).closest('tr').find('td:eq(1)').text().trim(),
            email: $(this).closest('tr').find('td:eq(2)').text().trim(),
            organization: $(this).closest('tr').find('td:eq(3)').text().trim(),
            phone: $(this).closest('tr').find('td:eq(4)').text().trim(),
            address: $(this).closest('tr').find('td:eq(5)').text().trim()
        });
        
        $.ajax({
            url: ajaxUrl,
            data: {
                id: id,
            },
            type: "get",
            dataType: "json",
            beforeSend: function() {
                // Show loading state
                $("#edituser .modal-body").addClass("loading");
                
                // Double-check form action is set before AJAX starts
                const currentAction = $("#editformuser").attr("action");
                console.log("Before AJAX - Form action is:", currentAction);
                if (!currentAction || currentAction === "") {
                    $("#editformuser").attr("action", formAction);
                    console.log("Form action was empty before AJAX, setting it to:", formAction);
                }
            },
            success: function (data) {
                console.log("=== SUCCESS: User data loaded ===");
                console.log("Raw response data:", data);
                console.log("User ID from response:", data.id);
                console.log("User name from response:", data.name);
                console.log("User email from response:", data.email);
                console.log("User organization from response:", data.organization);
                console.log("User phone from response:", data.phone);
                console.log("User address from response:", data.address);
                console.log("User role_id from response:", data.role_id);
                console.log("User status from response:", data.status);
                
                // Check if data is valid
                if (!data || !data.id) {
                    console.error("ERROR: Invalid data received from server");
                    console.error("Data:", data);
                    return;
                }
                
                // Verify the data matches the expected user
                const expectedName = $(this).closest('tr').find('td:eq(1)').text().trim();
                const expectedEmail = $(this).closest('tr').find('td:eq(2)').text().trim();
                console.log("=== DATA VERIFICATION ===");
                console.log("Expected name from table:", expectedName);
                console.log("Expected email from table:", expectedEmail);
                console.log("Received name from server:", data.name);
                console.log("Received email from server:", data.email);
                console.log("Names match:", expectedName === data.name);
                console.log("Emails match:", expectedEmail === data.email);
                
                // Populate form fields safely
                console.log("=== POPULATING FORM FIELDS ===");
                
                const idValue = data.id || id;
                $("#id").val(idValue);
                console.log("ID field set to:", idValue, "Actual value:", $("#id").val());
                
                const nameValue = data.name || '';
                $("#name").val(nameValue);
                console.log("Name field set to:", nameValue, "Actual value:", $("#name").val());
                
                const emailValue = data.email || '';
                $("#email").val(emailValue);
                console.log("Email field set to:", emailValue, "Actual value:", $("#email").val());
                
                const orgValue = data.organization || '';
                $("#organization").val(orgValue);
                console.log("Organization field set to:", orgValue, "Actual value:", $("#organization").val());
                
                const phoneValue = data.phone || '';
                $("#phone").val(phoneValue);
                console.log("Phone field set to:", phoneValue, "Actual value:", $("#phone").val());
                
                const addressValue = data.address || '';
                $("#address").val(addressValue);
                console.log("Address field set to:", addressValue, "Actual value:", $("#address").val());
                
                const passwordValue = '';
                $("#password").val(passwordValue);
                console.log("Password field set to:", passwordValue, "Actual value:", $("#password").val());
                
                const passwordConfirmValue = '';
                $("#password_confirmation").val(passwordConfirmValue);
                console.log("Password confirmation field set to:", passwordConfirmValue, "Actual value:", $("#password_confirmation").val());
                
                const roleValue = data.role_id || '';
                $("#role_id").val(roleValue);
                console.log("Role field set to:", roleValue, "Actual value:", $("#role_id").val());
                
                const statusValue = data.status || 'active';
                $("#status").val(statusValue);
                console.log("Status field set to:", statusValue, "Actual value:", $("#status").val());
                
                console.log("=== FORM POPULATION COMPLETE ===");
                
                // Log all form field values after population for debugging
                console.log("Final form field values:");
                console.log("ID field:", $("#id").val());
                console.log("Name field:", $("#name").val());
                console.log("Email field:", $("#email").val());
                console.log("Organization field:", $("#organization").val());
                console.log("Phone field:", $("#phone").val());
                console.log("Address field:", $("#address").val());
                console.log("Role field:", $("#role_id").val());
                console.log("Status field:", $("#status").val());
                
                // Test form elements after population
                console.log("Testing form elements after population...");
                window.testEditForm();
                
                // Set form action - use the correct route for Laravel resource routes
                const formAction = "/dashboard/users/" + data.id;
                $("#editformuser").attr("action", formAction);
                
                // Debug: Log form action
                console.log("Form action set to:", formAction);
                
                // Verify the form action was set correctly
                const actualAction = $("#editformuser").attr("action");
                console.log("Actual form action after setting:", actualAction);
                
                // Double-check: if action is not set, set it again
                if (!actualAction || actualAction === "") {
                    $("#editformuser").attr("action", formAction);
                    console.log("Form action was empty, setting it again to:", formAction);
                }
                
                // Remove loading state
                $("#edituser .modal-body").removeClass("loading");
                
                console.log("=== SUCCESS: Form population complete ===");
            },
            error: function(xhr, status, error) {
                console.error("=== ERROR: Failed to load user data ===");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response text:", xhr.responseText);
                console.error("Response status:", xhr.status);
                console.error("Response headers:", xhr.getAllResponseHeaders());
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.error("Parsed response:", response);
                } catch (e) {
                    console.error("Could not parse response as JSON:", e);
                }
                
                // Remove loading state
                $("#edituser .modal-body").removeClass("loading");
                
                // Show error message
                alert("Terjadi kesalahan saat memuat data user. Silakan coba lagi.\n\nError: " + error + "\nStatus: " + xhr.status);
                
                // Close modal
                $("#edituser").modal('hide');
            }
        });
    });
    
    // Debug form submission
    $("#editformuser").on("submit", function(e) {
        console.log("Form submitted!");
        console.log("Form action:", $(this).attr("action"));
        console.log("Form method:", $(this).attr("method"));
        console.log("Form data:", $(this).serialize());
        
        // Log specific fields for debugging
        console.log("Name field:", $("#name").val());
        console.log("Email field:", $("#email").val());
        console.log("Role field:", $("#role_id").val());
        console.log("Status field:", $("#status").val());
        
        // Check if form action is properly set
        let formAction = $(this).attr("action");
        console.log("Form submission - Current form action:", formAction);
        console.log("Form submission - Hidden ID field value:", $("#id").val());
        
        if (!formAction || formAction === "") {
            // Try to get the ID from the hidden field and set the action
            const userId = $("#id").val();
            if (userId) {
                formAction = "/dashboard/users/" + userId;
                $(this).attr("action", formAction);
                console.log("Form action was missing, setting it to:", formAction);
            } else {
                // Last resort: try to get ID from the button that was clicked
                const editButton = $(".edituser").filter(function() {
                    return $(this).data("id");
                }).first();
                if (editButton.length > 0) {
                    const buttonId = editButton.data("id");
                    formAction = "/dashboard/users/" + buttonId;
                    $(this).attr("action", formAction);
                    $("#id").val(buttonId);
                    console.log("Using ID from edit button:", buttonId);
                } else {
                    e.preventDefault();
                    console.error("Form action is not set and no user ID found! Preventing submission.");
                    alert("Terjadi kesalahan: Form action tidak terset. Silakan coba lagi.");
                    return false;
                }
            }
        }
        
        // Check if the _method field exists (for PUT method)
        const methodField = $(this).find('input[name="_method"]');
        if (methodField.length === 0) {
            console.error("_method field not found! This is needed for PUT requests.");
        } else {
            console.log("_method field found with value:", methodField.val());
        }
        
        // Log all form data including hidden fields
        console.log("All form data:");
        $(this).find('input, select, textarea').each(function() {
            console.log($(this).attr('name') + ': ' + $(this).val());
        });
        
        // Optionally prevent submission for debugging
        // e.preventDefault();
        // return false;
        
        // Ensure the form submits properly
        console.log("Form submission proceeding...");
    });
    
    // Test form when modal is shown
    $("#edituser").on("shown.bs.modal", function() {
        console.log("Edit user modal shown");
        console.log("Testing form elements when modal is shown...");
        window.testEditForm();
    });
});
