import React from 'react';
import clsx from 'clsx';

interface FormFieldProps {
  label: string;
  error?: string;
  required?: boolean;
  children: React.ReactNode;
  hint?: string;
  className?: string;
}

export const FormField: React.FC<FormFieldProps> = ({
  label,
  error,
  required,
  children,
  hint,
  className,
}) => {
  return (
    <div className={clsx('flex flex-col gap-1.5', className)}>
      <label className="text-xs font-semibold text-slate-300 flex items-center justify-between">
        <span>
          {label} {required && <span className="text-rose-400">*</span>}
        </span>
      </label>
      {children}
      {hint && !error && <p className="text-xs text-slate-500">{hint}</p>}
      {error && <p className="text-xs text-rose-400 font-medium">{error}</p>}
    </div>
  );
};
