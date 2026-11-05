<?php

namespace App\Http\Controllers;

class PackageLevelsAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        $levels = $this->package_levels->all();
        foreach ($levels as &$level) {
            $level['usage_count'] = $this->package_levels->usages($level);
        }unset($level);

        return $this->render('admin/package_levels', ['title' => 'ระดับบริการแพ็คเกจ', 'levels' => $levels], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $level = $id ? $this->package_levels->find($id) : ['id' => 0, 'name' => '', 'sort_order' => 0];
        if (! $level) {
            show_404();
        }$error = '';
        if (request_method() === 'post') {
            $name = request()->post('name');
            $sort = filter_var(request()->post('sort_order'), FILTER_VALIDATE_INT);
            $saved = $this->package_levels->save($id, $name, $sort);
            if ($saved) {
                session()->flash('success', 'บันทึกระดับบริการแล้ว');
                redirect_to(base_url().'admin/package-levels');
            }
            $error = $this->package_levels->error;
            $level['name'] = is_string($name) ? $name : '';
            $level['sort_order'] = $sort === false ? 0 : $sort;
        }

        return $this->render('admin/package_level_form', ['title' => $id ? 'แก้ไขระดับบริการ' : 'เพิ่มระดับบริการ', 'level' => $level, 'error' => $error, 'existing_levels' => $id ? [] : $this->package_levels->all(), 'import_rows' => []], true);
    }

    public function import()
    {
        $this->post_only();
        $rows = request()->post('levels');
        $count = $this->package_levels->save_many($rows);
        if ($count) {
            session()->flash('success', 'เพิ่มระดับบริการ '.$count.' ระดับแล้ว');
            redirect_to(base_url().'admin/package-levels');
        }
        $import_rows = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row)) {
                $import_rows[] = ['name' => is_string($row['name'] ?? null) ? $row['name'] : '', 'sort_order' => (int) filter_var($row['sort_order'] ?? null, FILTER_VALIDATE_INT)];
            }
        }

        return $this->render('admin/package_level_form', ['title' => 'เพิ่มระดับบริการ', 'level' => ['id' => 0, 'name' => '', 'sort_order' => 0], 'error' => $this->package_levels->error, 'existing_levels' => $this->package_levels->all(), 'import_rows' => $import_rows], true);
    }

    public function delete($id)
    {
        $this->post_only();
        $ok = $this->package_levels->delete((int) $id);
        session()->flash($ok ? 'success' : 'error', $ok ? 'ลบระดับบริการแล้ว' : $this->package_levels->error);
        redirect_to(base_url().'admin/package-levels');
    }
}
