import React from 'react';

interface StructuredDataProps {
  data: Record<string, any>;
}

export const StructuredData: React.FC<StructuredDataProps> = ({ data }) => {
  if (!data || Object.keys(data).length === 0) return null;

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
};
