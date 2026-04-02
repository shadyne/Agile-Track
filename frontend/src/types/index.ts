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

export interface StatusItem {
  status: string;
  label: string;
  total: number;
  color: string;
  bg_color: string;
  text_color: string;
  category: string;
  sort_order: number;
}

export interface DashboardData {
  space: string;
  summary: DashboardSummary;
  priority_breakdown: Record<string, number>;
  status_overview: StatusItem[];
}

export interface GlobalDashboardData {
  summary: DashboardSummary;
  priority_breakdown: Record<string, number>;
  status_overview: StatusItem[];
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

export type EpicItemType = "story" | "task" | "qa_task" | "bug";
export type EpicItemStatus =
  | "to_do"
  | "in_progress"
  | "done_by_dev"
  | "testing"
  | "done_by_qa";
export type EpicItemPriority = "highest" | "high" | "medium" | "low" | "lowest";

export interface EpicItem {
  id: number;
  epic_id: number | null;
  parent_id: number | null;
  board_id: number;
  sprint_id: number | null;
  kode: string;
  judul: string;
  deskripsi: string | null;
  label: string | null;
  labels: string[];
  type: EpicItemType;
  status: EpicItemStatus;
  priority: EpicItemPriority;
  start_date: string | null;
  end_date: string | null;
  original_estimate: string | null;
  assignee_id?: number | null;
  assignee?: { id: number; name: string; email: string } | null;
  user?: { id: number; name: string; email: string };
  epic?: { id: number; kode: string; judul: string; labels: string[] } | null;
  children?: EpicItem[];
}

export interface EpicItemForm {
  judul: string;
  type: EpicItemType;
  priority: EpicItemPriority;
  labels: string[];
  start_date: string;
  end_date: string;
  deskripsi: string;
  assignee_id: number | null;
  epic_id: number | null;
  parent_id: number | null;
  sprint_id: number | null;
  original_estimate: string;
  attachments: File[];
}

export interface Sprint {
  id: number;
  board_id: number;
  nama: string;
  start_date: string | null;
  end_date: string | null;
  status: "planning" | "active" | "completed";
  items: EpicItem[];
}

export interface BacklogData {
  backlog: EpicItem[];
  sprints: Sprint[];
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
  type: "epic" | "story" | "task" | "qa_task" | "bug";
  status: EpicItemStatus;
  priority: EpicItemPriority;
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
  priority: EpicItemPriority;
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
