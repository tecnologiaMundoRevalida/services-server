<!doctype html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Prova</title>

    <style>
        body {
            background-image: url("{{ $logoBackground }}");
            /*background-position: bottom right;*/
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100%;
        }

        .alternative {
            align-items: center;
            width: fit-content;
            cursor: pointer;
        }

        .option {
            justify-content: center;
            align-items: center;
            width: 25px;
            min-width: 25px;
            height: 25px;
            background: transparent;
            color: black;
            margin-right: 1rem;
            border-radius: 4px;
            transition: 0.5s;
            border: 2px solid black;
            font-weight: 600;
            line-height: 100%;
            padding: 5px;
        }

        .span-specialty {
            background-color: #9ca3af;
            font-size: {{ $fontSize['medicine_area'] }};
            padding: 3px;
            border-radius: 4px;
        }

        .img-logo-medhit {
            max-width: 200px;
            height: auto;
            margin: 0;
            padding: 0;
        }

        .title-question {
            font-size: {{ $fontSize['title_question'] }};
        }

        .subtitle-question {
            font-size: {{ $fontSize['subtitle_question'] }};
        }

        .question-description {
            font-size: {{ $fontSize['question_description'] }};
        }

        .alternative {
            font-size: {{ $fontSize['alternative'] }};
        }

        .annulled {
            width: fit-content;
            font-size: {{ $fontSize['annulled_question'] }};
            color: #fff;
            margin-bottom: 1rem;
            padding: 0.2rem 0.5rem;
            border-radius: 50px;
            background: red !important;
        }

        .number-question {
            display:inline-block;
            border: 1px solid black;
            border-right: none;
            padding: 5px;
            margin-right: 0;
            margin-top: 20px;
        }

        .answer {
            display:inline-block;
            border: 1px solid black;
            border-left: none;
            margin-left: 0;
            margin-right: 10px;
            padding: 5px;
            background-color: #9ca3af;
            margin-top: 20px;
        }

    </style>
</head>
<body>
    <div>
        <h1><img class="img-logo-medhit" src="{{ public_path('images/LOGOTIPO_MEDTASK_CMYK-13.png') }}"></h1>
        <hr>
    </div>

    @php
        $questionNumber = 0;
    @endphp

    @foreach($data as $key => $question)
        @php
            $questionNumber++;
        @endphp
        <div>
            <div>
                <h2 class="title-question">Questão {{ $questionNumber }}  {!! $question->medicine_area ?? '' !!}</h2>
                <h4 class="subtitle-question">{{ $question->name_institution }} - {{ $question->name_year }}</h4>
                @if($question->is_annulled)
                    <span class="annulled">QUESTÃO ANULADA</span>
                @endif
            </div>

            <div class="question-description">
                {!! mb_convert_encoding(strip_tags($question->question, '<p><img><title>'), 'UTF-8', 'auto') !!}
            </div>
            <br>

            @foreach($question->alternatives as $alternative)
                <div class="alternative">
                    <p> <span class="option">{{ $alternative['option'] }}</span> {{ $alternative['alternative'] }} </p>
                </div>
            @endforeach
            <hr>
        </div>
    @endforeach

    <h3>GABARITO</h3>
    @foreach($answerKey as $answer)
        <span class="number-question">{{ $answer['question'] }}</span>
        <span class="answer">{{ $answer['correct_alternative'] ?? '' }}</span>
    @endforeach
</body>
</html>
