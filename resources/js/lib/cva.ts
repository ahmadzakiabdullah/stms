type Variants = Record<string, Record<string, string>>;

type Config = {
    variants?: Variants;
    defaultVariants?: Record<string, string>;
};

type ClassValue = string | false | null | undefined;

export function cva(base: string, config: Config = {}) {
    return (props: Record<string, string | ClassValue> = {}) => {
        const classes: string[] = [base];
        const variants = config.variants ?? {};

        Object.entries(variants).forEach(([name, values]) => {
            const value = String(props[name] ?? config.defaultVariants?.[name] ?? '');
            if (values[value]) classes.push(values[value]);
        });

        const className = props.className;
        if (typeof className === 'string' && className.trim()) classes.push(className);

        return classes.filter(Boolean).join(' ');
    };
}
