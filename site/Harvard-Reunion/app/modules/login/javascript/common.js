function validateAnonymousForm() {
    if(document.getElementById("year").value=="") {
        alert("Please select a graduation year.")
        return false;
    }
}
