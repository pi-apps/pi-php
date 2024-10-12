# firewall_creation.rb
class Firewall
    def initialize
      @rules = []
    end
  
    def add_rule(rule)
      @rules << rule
      puts "Added firewall rule: #{rule}"
    end
  
    def show_rules
      puts "Current firewall rules:"
      @rules.each { |rule| puts rule }
    end
  end
  
  firewall = Firewall.new
  firewall.add_rule("Block all incoming connections with Pi price set to $3141540")
  firewall.show_rules
  puts "This does not change the price of Pi in any meaningful way."
  