<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
  
    <head>
        <link rel="stylesheet" type="text/css" hs-webfonts="true" href="https://fonts.googleapis.com/css?family=Lato|Lato:i,b,bi">
        <title>Conta criada com sucesso</title>
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
                background-color: #bf9213;
                color: #ffffff !important;
                border-radius: 8px; 
                margin-left: auto;
                margin-right: auto;
            }
        
        </style>
            
    </head>
            
    <body style="width: 100%; margin: auto 0; padding:1rem; font-family:Lato, sans-serif; font-size:18px; color:#33475B; word-break:break-word; box-sizing: border-box;">
        
        <div style="margin-bottom: 1.5rem">
            <h1 style="text-align:center; font-size: 28px; font-weight: bold">Sua conta foi criada com sucesso!</h1>
        </div>
        <div style="margin-bottom: 1.5rem">
            <p style="text-align:center; font-size: 18px;">Olá {{ $user->name }}</p>
        </div>
        <div style="margin-bottom: 1.5rem">
            <p style="text-align:center; font-size: 18px; line-height: 140%">Sua conta no MedTask foi criada com sucesso!<br>Para acessar, basta preencher o usuário e a senha no link abaixo.</p>
        </div>
        <div style="margin-bottom: .5rem">
            <p style="text-align:center; font-size: 18px; line-height: 140%; font-weight: bold">usuário: {{ $user->email }}</p>
        </div>
        <div style="margin-bottom: 2rem">
            <p style="text-align:center; font-size: 18px; line-height: 140%; font-weight: bold">senha: {{ $password }}</p>
        </div>
        <div style="display:flex; justify-content:center">
            @if ($type == 'user-banco-questoes')
                <a class="link" href="https://medtask.com.br" target="_blank">Ir para o site</a>
            @else
                <a class="link" href="https://medtask.com.br" target="_blank">Ir para o site</a>
            @endif
            
        </div>

    </body>
</html>