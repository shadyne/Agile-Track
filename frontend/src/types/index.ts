export interface User {
  id: number;
  name: string;
  email: string;
}

export interface LoginForm {
  email: string;
  password: string;
  remember: boolean;
}

export interface RegisterForm {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  message: string;
  token: string;
  user: User;
}

export interface DashboardSummary {
  completed: number;
  updated: number;
  created: number;
  due_soon: number;
}

export interface DashboardData {
  space: string;
  summary: DashboardSummary;
  priority_breakdown: Record<string, number>;
  status_overview: Record<string, number>;
}

export interface ActivityItem {
  id: number;
  user_name: string;
  field_updated: string;
  task_code: string;
  time_ago: string;
}

export interface ActivityGroup {
  date_label: string;
  activities: ActivityItem[];
}

export interface Space {
  id: number;
  nama: string;
  key: string;
  member_emails: string[];
  tasks_count?: number;
}
export interface SpaceForm {
  nama: string;
  key: string;
  member_emails: string[];
}

export interface Board {
  id: number;
  space_id: number;
  nama: string;
  framework: "scrum" | "kanban";
  member_emails: string[];
}

export interface BoardForm {
  nama: string;
  framework: "scrum" | "kanban";
  member_emails: string[];
}

export interface EpicItem {
  id: number;
  epic_id: number;
  board_id: number;
  kode: string;
  judul: string;
  deskripsi: string | null;
  label: string | null;
  type: "story" | "task" | "bug";
  status: "to_do" | "in_progress" | "done_by_dev" | "testing" | "done_by_qa";
  priority: "highest" | "high" | "medium" | "low" | "lowest";
  start_date: string | null;
  end_date: string | null;
}

export interface Epic {
  id: number;
  board_id: number;
  user_id: number;
  assignee_id?: number;
  kode: string;
  judul: string;
  deskripsi?: string;
  original_estimate?: string;
  labels: string[];
  type: "epic" | "story" | "task" | "bug";
  status: "to_do" | "in_progress" | "done_by_dev" | "testing" | "done_by_qa";
  priority: "highest" | "high" | "medium" | "low" | "lowest";
  start_date?: string;
  end_date?: string;
  items: EpicItem[];
  attachments?: EpicAttachment[];
  comments?: EpicComment[];
  user?: { id: number; name: string; email: string };
  assignee?: { id: number; name: string; email: string };
}

export interface EpicForm {
  judul: string;
  priority: "highest" | "high" | "medium" | "low" | "lowest";
  labels: string[];
  start_date: string;
  end_date: string;
  deskripsi: string;
  assignee_id: number | null;
  attachments: File[];
}

export interface EpicAttachment {
  id: number;
  epic_id: number;
  nama_file: string;
  path: string;
  mime_type: string;
  ukuran: number;
}

export interface EpicComment {
  id: number;
  epic_id: number;
  user_id: number;
  isi: string;
  created_at: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
}

export interface EpicHistory {
  id: number;
  user_name: string;
  user_initial: string;
  field_updated: string;
  new_value: string;
  time_ago: string;
}
