<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
  
    <head>
        <link rel="stylesheet" type="text/css" hs-webfonts="true" href="https://fonts.googleapis.com/css?family=Lato|Lato:i,b,bi">
        <title>Redefinir Senha</title>
        <meta property="og:title" content="Email template">
    
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">
            
        <style type="text/css">
    
            a{ 
                text-decoration: none;
                color: inherit;
                font-weight: bold;
                color: #253342;
            }
            
            h1 {
                font-size: 56px;
            }
            
            h2{
                font-size: 28px;
                font-weight: 900; 
            }
            
            p {
                font-weight: 100;
                margin: 0;
            }
            
            .link{
                padding: .5rem 2rem;
                background-color: #d93e3e;
                color: #ffffff !important;
                border-radius: 8px; 
                margin-left: auto;
                margin-right: auto;
            }
        
        </style>
            
    </head>
            
    <body style="width: 100%; margin: auto 0; padding:1rem; font-family:Lato, sans-serif; font-size:18px; color:#33475B; word-break:break-word; box-sizing: border-box;">

        <div style="max-width: 600px; margin: 0 auto">
            <div style="background: #070707; padding: 1rem; display: flex; justify-content: center">
                <img src="{{url('/assets/images/medhit.png')}}" alt="Medhit" style="max-width: 100%; height: auto; margin: 0 auto;">
            </div>
            <div style="margin-bottom: 1.5rem">
                <h1 style="text-align:center; font-size: 28px; font-weight: bold">Redefinir Senha</h1>
            </div>
            <div style="margin-bottom: 1.5rem">
                <p style="text-align:left; font-size: 18px; line-height: 140%">Você solicitou uma redefinição de senha. Clique no botão abaixo para redefinir uma nova senha.</p>
            </div>
            <div style="display:flex; justify-content:center; margin-bottom: 1.5rem">
                <a class="link" href="http://medtask.medhit.com.br/redefinir-senha?e={{$email}}&t={{$token}}" target="_blank">Definir nova senha</a>
            </div>
            <div style="margin-bottom: 1.5rem">
                <p style="text-align:left; font-size: 18px; line-height: 140%">Caso você não tenha solicitado a redefinição de senha, apenas ignore esta mensagem.</p>
            </div>
        </div>

    </body>
</html>