async function autoLogin(){

    try{

        const existingToken =
        localStorage.getItem(
            "kst_access_token"
        );

        if(existingToken){
            return;
        }

        const response =
        await apiRequest(
            "/auth/login",
            "POST",
            {
                username:"kstcangar_wp",
                password:"Sin3020mm25mt44!"
            }
        );

        localStorage.setItem(
            "kst_access_token",
            response.accessToken
        );

        location.reload();

    }catch(err){

        console.error(
            "login failed",
            err
        );

    }

}

document.addEventListener(
    "DOMContentLoaded",
    autoLogin
);
