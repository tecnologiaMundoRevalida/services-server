<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ResumoPDF</title>
    <style>
       body {
            background-image: url("https://mundo-revalida-checklist-images.s3.amazonaws.com/images/logo-medtask.png");
            background-repeat: repeat;
            background-size: contain;
        }

        .footer {
            font-size: 12px;
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
          }
          .footer .page:after{
            content: counter(page);
          }
          .head{
            font-size: 12px;
            height: 100px;
            width: 100%;
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            margin: auto;
          }
          .main-content{
                width: 600px;
                position: relative;
                margin: auto;
          }
    </style>
</head>
<body>
    <p>Resumo emitido por: {!! $user->name !!}, CPF: {!! $user->document_number !!}</p>
    {!! $resumeAll; !!}
    <p>Resumo emitido por: {!! $user->name !!}, CPF: {!! $user->document_number !!}</p>
    <div class="footer">{!! $user->name !!}, CPF: {!! $user->document_number !!}</div>
</body>
</html>
