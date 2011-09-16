/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

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
function validateHarrisForm() {
    if (!formCancelButtonPressed && document.getElementById("username").value=="") {
        alert("Please enter your username.")
        return false;
    }
    if (!formCancelButtonPressed && document.getElementById("pwd").value=="") {
        alert("Please enter your password.")
        return false;
    }
}
