function validateForm()  {
    var e = document.getElementById("email").value;
    var u = document.getElementById("username").value;
    var p = document.getElementById("password").value;

    if(e== "") {
        document.getElementById("erremail").innerHTML = "Không được để trống Email";
        return false;
    }
    if(u== "") {
        document.getElementById("erruser").innerHTML = "Không được để trống username";
        return false;
    }
    if(p == "") {
        document.getElementById("errpass").innerHTML = "Không được để trống pass";
        return false;
    }
    if(p => "6") {
        document.getElementById("errpass").innerHTML = "Pass phải 6 ký tự trowrw lên"
                 return false;
             }

    return true;
}