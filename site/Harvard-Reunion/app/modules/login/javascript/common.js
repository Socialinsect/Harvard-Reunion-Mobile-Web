function validateAnonymousForm() {
    if(document.getElementById("year").value=="") {
        alert("Please select a graduation year.")
        return false;
    }
}

function validateSelectCollegeForm() {
    if(document.getElementById("collegeIndex").value=="") {
        alert("Please select your college.")
        return false;
    }
}
