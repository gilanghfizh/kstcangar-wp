async function apiRequest(
    endpoint,
    method = "GET",
    body = null
){

    const token =
    localStorage.getItem(
        "kst_access_token"
    );

    const options = {

        method,

        headers:{
            "Content-Type":"application/json"
        }

    };

    if(token){

        options.headers.Authorization =
        `Bearer ${token}`;

    }

    if(body){

        options.body =
        JSON.stringify(body);

    }

    const response =
    await fetch(
        `${kstConfig.baseUrl}${endpoint}`,
        options
    );

    const result =
    await response.json();

    if(!response.ok){

        throw new Error(
            result?.error?.message ||
            "API Error"
        );

    }

    return result.response ?? result;

}
