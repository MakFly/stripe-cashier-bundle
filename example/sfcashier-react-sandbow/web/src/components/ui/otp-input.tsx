import { useRef, useEffect, forwardRef } from "react";
import { Input } from "./input";
import { cn } from "../../lib/utils";

interface OtpInputProps {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  autoFocus?: boolean;
  length?: number;
}

export const OtpInput = forwardRef<HTMLDivElement, OtpInputProps>(
  ({ value, onChange, disabled = false, autoFocus = true, length = 6 }, ref) => {
    const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

    // Focus first input on mount if autoFocus is true
    useEffect(() => {
      if (autoFocus && inputRefs.current[0]) {
        inputRefs.current[0]?.focus();
      }
    }, [autoFocus]);

    // Handle input change
    const handleChange = (index: number, newValue: string) => {
      // Only allow numbers
      const numValue = newValue.replace(/[^0-9]/g, "");

      if (numValue.length === 0) {
        // Clear the current digit
        const newOtp = value.split("");
        newOtp[index] = "";
        onChange(newOtp.join(""));

        // Focus previous input
        if (index > 0) {
          inputRefs.current[index - 1]?.focus();
        }
      } else if (numValue.length === 1) {
        // Set the digit
        const newOtp = value.split("");
        newOtp[index] = numValue;
        onChange(newOtp.join(""));

        // Focus next input
        if (index < length - 1) {
          inputRefs.current[index + 1]?.focus();
        }
      }
    };

    // Handle keydown
    const handleKeyDown = (
      index: number,
      e: React.KeyboardEvent<HTMLInputElement>,
    ) => {
      if (e.key === "Backspace" && !value[index] && index > 0) {
        // If current input is empty and backspace is pressed, focus previous
        inputRefs.current[index - 1]?.focus();
      } else if (e.key === "ArrowLeft" && index > 0) {
        inputRefs.current[index - 1]?.focus();
      } else if (e.key === "ArrowRight" && index < length - 1) {
        inputRefs.current[index + 1]?.focus();
      }
    };

    // Handle paste
    const handlePaste = (e: React.ClipboardEvent<HTMLInputElement>) => {
      e.preventDefault();
      const pastedData = e.clipboardData.getData("text/plain").replace(/[^0-9]/g, "");

      if (pastedData.length > 0) {
        const newOtp = value.split("");
        let pasteIndex = 0;

        for (let i = 0; i < length && pasteIndex < pastedData.length; i++) {
          if (!newOtp[i]) {
            newOtp[i] = pastedData[pasteIndex];
            pasteIndex++;
          }
        }

        onChange(newOtp.join(""));

        // Focus the next empty input or the last one
        const nextEmptyIndex = newOtp.findIndex((val) => !val);
        if (nextEmptyIndex !== -1 && nextEmptyIndex < length) {
          inputRefs.current[nextEmptyIndex]?.focus();
        } else {
          inputRefs.current[length - 1]?.focus();
        }
      }
    };

    return (
      <div ref={ref} className="flex gap-2 justify-center">
        {Array.from({ length }).map((_, index) => (
          <Input
            key={index}
            ref={(el) => {
              inputRefs.current[index] = el;
            }}
            type="text"
            inputMode="numeric"
            pattern="[0-9]"
            maxLength={1}
            value={value[index] ?? ""}
            onChange={(e) => handleChange(index, e.target.value)}
            onKeyDown={(e) => handleKeyDown(index, e)}
            onPaste={handlePaste}
            disabled={disabled}
            className={cn(
              "w-12 h-14 text-center text-xl font-semibold",
              "focus-visible:ring-2 focus-visible:ring-primary",
              "disabled:opacity-50 disabled:cursor-not-allowed",
            )}
            autoComplete="one-time-code"
          />
        ))}
      </div>
    );
  },
);

OtpInput.displayName = "OtpInput";
