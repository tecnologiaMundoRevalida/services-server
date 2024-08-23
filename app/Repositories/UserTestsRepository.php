<?php

namespace App\Repositories;

use App\Models\UserTest;
use Illuminate\Support\Facades\DB;
use App\Models\UserTestReference;
use Illuminate\Support\Facades\Auth;
use App\Models\Question;

class UserTestsRepository
{
    private $modelUserTest;

    public function __construct(UserTest $modelUserTest)
    {
        return $this->modelUserTest = $modelUserTest;
    }


    public function store($data)
    {
        $userTest = $this->modelUserTest->create($data);
        $query = $this->buildBaseQuery();
        $this->applyFilters($query,$data);
        $questions = $this->executeQuery($query,$data);
        foreach($questions as $q)
       {
            UserTestReference::create(['user_test_id' => $userTest->id,"question_id" => $q->id]);
       }

       return $userTest;
    }

    private function buildBaseQuery()
    {
        $user_id = Auth::id();
        return DB::table('questions as q')
            ->select("q.id")
            ->join("tests as t",function($join){
                $join->on("t.id","=","q.test_id")
                    ->whereNull("t.status");            
            })
            ->leftJoin("medicine_area_references as mar","mar.question_id","=","q.id")
            ->leftJoin("specialty_references as sr","sr.question_id","=","q.id")
            ->leftJoin("theme_references as tr","tr.question_id","=","q.id")
            ->leftJoin('questions_answered as qa', function($join) use ($user_id) {
                $join->on('q.id', '=', 'qa.question_id')
                    ->where('qa.user_id', '=', $user_id);
            });
    }

    private function applyFilters(&$query, $body)
    {
        $this->applyFilter($query, $body, "is_discursive", "q.is_discursive");
        $this->applyFilter($query, $body, "is_comment", "q.explanation", true);
        $this->applyArrayFilter($query, $body, "tests", "t.id");
        $this->applyArrayFilter($query, $body, "areas", "mar.medicine_area_id");
        $this->applyArrayFilter($query, $body, "specialties", "sr.specialty_id");
        $this->applyArrayFilter($query, $body, "themes", "tr.theme_id");
    }
    
    private function applyFilter(&$query, $body, $key, $column, $notNull = false)
    {
        if(!empty($body[$key])){
            $notNull ? $query->whereNotNull($column) : $query->where($column, $body[$key]);
        }
    }
    
    private function applyArrayFilter(&$query, $body, $key, $column)
    {
        if(!empty($body[$key]) && is_array($body[$key])){
            if($key == "tests" || $key == "specialties" || $key == "themes"){
                $body[$key] = array_map(function($item){
                    return json_decode($item)->id; 
                }, $body[$key]);
            }
            $query->whereIn($column, $body[$key]);
        }
    }

    private function executeQuery($query, $body)
    {
        if(!empty($body["qtd_questions"])){
            return $query->distinct()->limit($body["qtd_questions"])->orderBy('qa.id', 'asc')->get();
        }
    
        return $query->distinct()->limit(100)->orderBy('qa.id', 'asc')->get();
    }

}