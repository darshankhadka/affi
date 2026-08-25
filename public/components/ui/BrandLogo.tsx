import React from 'react';
import Image from 'next/image';

interface BrandLogoProps {
  className?: string;
  width?: number;
  height?: number;
  priority?: boolean;
}

export const BrandLogo: React.FC<BrandLogoProps> = ({
  className = 'h-9 w-auto',
  width = 250,
  height = 100,
  priority = true,
}) => {
  return (
    <div className={`relative inline-flex items-center shrink-0 ${className}`}>
      <Image
        src="/logo.svg"
        alt="ARIKARTECH - Global Tech Engine"
        width={width}
        height={height}
        priority={priority}
        className="h-full w-auto object-contain select-none"
        style={{ aspectRatio: '250 / 100' }}
      />
    </div>
  );
};
