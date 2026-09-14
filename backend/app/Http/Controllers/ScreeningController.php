<?php

namespace App\Http\Controllers;

use App\Models\Screening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScreeningController extends Controller
{
    /**
     * 排期列表
     * 支持参数：movie_id（按影片筛选）、scope=upcoming|past|all（近期/历史/全部）
     */
    public function index(Request $request)
    {
        $query = Screening::with('movie:id,title,translated_title,year');

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        $scope = $request->input('scope', 'all');
        if ($scope === 'upcoming') {
            $query->upcoming()
                  ->orderBy('screening_date')
                  ->orderBy('start_time');
        } elseif ($scope === 'past') {
            $query->past()
                  ->orderBy('screening_date', 'desc')
                  ->orderBy('start_time', 'desc');
        } else {
            $query->orderBy('screening_date', 'desc')
                  ->orderBy('start_time', 'desc');
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    /**
     * 新增排期
     */
    public function store(Request $request)
    {
        $input = $this->normalizeInput($request->all());

        $validator = $this->makeValidator($input);
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $screening = Screening::create($validator->validated());

        return response()->json($screening, 201);
    }

    /**
     * 更新排期
     */
    public function update(Request $request, $id)
    {
        $screening = Screening::find($id);
        if (!$screening) {
            return response()->json(['error' => 'Screening not found'], 404);
        }

        $input = $this->normalizeInput($request->all());

        $validator = $this->makeValidator($input);
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $screening->update($validator->validated());

        return response()->json($screening->fresh());
    }

    /**
     * 删除排期
     */
    public function destroy($id)
    {
        $screening = Screening::find($id);
        if (!$screening) {
            return response()->json(['error' => 'Screening not found'], 404);
        }

        $screening->delete();

        return response()->json(['status' => 'success']);
    }

    /**
     * 统一处理输入：场次时间兼容 "HH:MM" 与 "HH:MM:SS"
     */
    private function normalizeInput(array $input): array
    {
        if (isset($input['start_time'])) {
            $input['start_time'] = substr(trim((string) $input['start_time']), 0, 5);
        }

        return $input;
    }

    private function makeValidator(array $data)
    {
        return Validator::make($data, [
            'movie_id' => 'required|integer|exists:movies,id',
            'location' => 'required|string|max:255',
            'screening_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ], [
            'movie_id.required' => '缺少影片信息',
            'movie_id.exists' => '关联的影片不存在',
            'location.required' => '请填写放映地点',
            'location.max' => '放映地点过长',
            'screening_date.required' => '请选择放映日期',
            'screening_date.date_format' => '放映日期格式不正确',
            'start_time.required' => '请填写场次时间',
            'start_time.date_format' => '场次时间格式不正确（HH:MM）',
            'contact_name.required' => '请填写联系人',
            'contact_phone.max' => '联系电话过长',
        ]);
    }
}
