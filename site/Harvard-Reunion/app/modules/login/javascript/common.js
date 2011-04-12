var formCancelButtonPressed = false;

function validateAnonymousForm() {
    if(!formCancelButtonPressed && document.getElementById("year").value=="") {
        alert("Please select a graduation year.")
        return false;
    }
}

function validateSelectCollegeForm() {
    if (!formCancelButtonPressed && document.getElementById("collegeIndex").value=="") {
        alert("Please select your college.")
        return false;
    }
}
